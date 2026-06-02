<?php
require_once __DIR__ . '/config.php';

class VideoAnalysisAgent {
    public static function isConfigured(): bool {
        return OPENAI_API_KEY !== '';
    }

    public static function analyze(array $context): array {
        $fallback = self::buildFallbackAnalysis(
            $context,
            !self::isConfigured() ? 'OPENAI_API_KEY absent. Analyse de demonstration utilisee.' : null
        );

        if (!self::isConfigured()) {
            return $fallback;
        }

        try {
            return self::requestOpenAIAnalysis($context);
        } catch (Throwable $e) {
            if (!AI_ALLOW_DEMO_FALLBACK) {
                throw $e;
            }

            $fallback['analysis_error'] = $e->getMessage();
            return $fallback;
        }
    }

    private static function requestOpenAIAnalysis(array $context): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL PHP est requis pour appeler OpenAI.');
        }

        $frames = self::normalizeFrames($context['frames'] ?? []);

        if ($frames === []) {
            throw new RuntimeException('Aucune image exploitable n a ete extraite de la video.');
        }

        $content = [[
            'type' => 'input_text',
            'text' => self::buildPrompt($context, $frames),
        ]];

        foreach ($frames as $index => $frame) {
            $content[] = [
                'type' => 'input_text',
                'text' => self::buildFrameCaption($frame, $index, count($frames)),
            ];
            $content[] = [
                'type' => 'input_image',
                'image_url' => $frame['image'],
            ];
        }

        $payload = [
            'model' => OPENAI_MODEL,
            'temperature' => 0.1,
            'max_output_tokens' => 1400,
            'input' => [[
                'role' => 'user',
                'content' => $content,
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'atlas_video_rating',
                    'schema' => self::responseSchema(),
                    'strict' => true,
                ],
            ],
        ];

        $ch = curl_init(OPENAI_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . OPENAI_API_KEY,
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => OPENAI_TIMEOUT,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            throw new RuntimeException('Echec reseau OpenAI: ' . $curlError);
        }

        $response = json_decode($raw, true);

        if (!is_array($response)) {
            throw new RuntimeException('Reponse OpenAI invalide.');
        }

        if ($httpCode >= 400) {
            $message = $response['error']['message'] ?? ('Erreur OpenAI HTTP ' . $httpCode);
            throw new RuntimeException($message);
        }

        $json = self::extractJsonText($response);

        if ($json === null) {
            throw new RuntimeException('OpenAI n a retourne aucun JSON exploitable.');
        }

        $analysis = json_decode($json, true);

        if (!is_array($analysis)) {
            throw new RuntimeException('JSON d analyse OpenAI invalide.');
        }

        $normalized = self::normalizeAnalysis($analysis, array_merge($context, ['frames' => $frames]));
        $normalized['provider'] = 'openai';
        $normalized['model'] = $response['model'] ?? OPENAI_MODEL;
        $normalized['usage'] = $response['usage'] ?? null;
        $normalized['raw_response_id'] = $response['id'] ?? null;

        return $normalized;
    }

    private static function normalizeFrames(array $frames): array {
        $normalized = [];

        foreach (array_slice($frames, 0, AI_FRAME_LIMIT) as $index => $frame) {
            if (is_string($frame) && str_starts_with($frame, 'data:image/')) {
                $normalized[] = [
                    'image' => $frame,
                    'timestamp' => null,
                    'ratio' => null,
                    'motion' => null,
                    'sharpness' => null,
                    'brightness' => null,
                    'role' => self::defaultFrameRole($index, AI_FRAME_LIMIT),
                ];
                continue;
            }

            if (!is_array($frame)) {
                continue;
            }

            $image = trim((string) ($frame['image'] ?? $frame['data_url'] ?? ''));

            if ($image === '' || !str_starts_with($image, 'data:image/')) {
                continue;
            }

            $normalized[] = [
                'image' => $image,
                'timestamp' => isset($frame['timestamp']) ? round((float) $frame['timestamp'], 2) : null,
                'ratio' => isset($frame['ratio']) ? max(0, min(1, (float) $frame['ratio'])) : null,
                'motion' => isset($frame['motion']) ? max(0, min(1, (float) $frame['motion'])) : null,
                'sharpness' => isset($frame['sharpness']) ? max(0, min(1, (float) $frame['sharpness'])) : null,
                'brightness' => isset($frame['brightness']) ? max(0, min(1, (float) $frame['brightness'])) : null,
                'role' => self::sanitizeRole($frame['role'] ?? self::defaultFrameRole($index, AI_FRAME_LIMIT)),
            ];
        }

        return $normalized;
    }

    private static function buildPrompt(array $context, array $frames): string {
        $student = $context['student'] ?? [];
        $meta = $context['meta'] ?? [];
        $frameCount = count($frames);
        $sportGuidance = self::sportSpecificGuidance($student, (string) ($context['perf_type'] ?? ''));
        $observabilityNotes = self::observabilityNotes($context, $frames);

        $parts = [
            'You are Atlas Talents, an expert youth sports scouting analyst.',
            'Your job is to evaluate only what is visually supported by the chronological keyframes and the provided metadata.',
            'Return valid JSON only. Do not add markdown.',
            'Write every user-facing string in French.',
            'Be conservative. Separate observable facts from uncertain inference.',
            'If a criterion is weakly supported, lower the score, lower confidence, and mention the limitation explicitly.',
            'Keep scores internally consistent: score_global should stay close to the average of the five criteria unless a clear standout reason exists.',
            'Context:',
            '- Student: ' . ($student['name'] ?? 'Unknown'),
            '- Age: ' . (string) ($student['age'] ?? 'unknown'),
            '- Sport: ' . ($student['sport'] ?? 'unknown'),
            '- City: ' . ($student['ville'] ?? 'unknown'),
            '- Performance type: ' . ($context['perf_type'] ?? 'unknown'),
            '- Frame count: ' . $frameCount,
            '- Video duration seconds: ' . (string) round((float) ($meta['duration'] ?? 0), 2),
            '- Video width x height: ' . (string) ($meta['width'] ?? 0) . 'x' . (string) ($meta['height'] ?? 0),
            '- Frame selection strategy: ' . (($meta['selection_strategy'] ?? '') !== '' ? (string) $meta['selection_strategy'] : 'chronological keyframes'),
            'Scoring rubric:',
            '- vitesse: acceleration, explosiveness, speed cues, displacement quality',
            '- coordination: balance, body control, timing, posture, rhythm, clean sequencing',
            '- endurance: sustained effort clues only if visible over enough time',
            '- force: power generation, stability, force transfer, body drive if visible',
            '- souplesse: range of motion, fluidity, mobility, amplitude if visible',
            '- score_global: overall potential shown by this sequence, not lifetime potential',
            'Mandatory behavior:',
            '- Use integers from 0 to 100.',
            '- Confidence must reflect evidence quality, not optimism.',
            '- Mention what cannot be verified from still images or short duration.',
            '- Avoid medical claims and avoid inventing timings, distances, or measurements.',
            '- Strengths and improvements must be specific and coach-usable.',
            '- Recruiter highlights must stay concise and factual.',
            'Evidence-quality guide:',
            '- strong: the movement pattern is clearly visible across multiple frames and duration supports the claim',
            '- moderate: partially visible but still plausible',
            '- limited: unclear angle, too little duration, occlusion, blur, or still-frame ambiguity',
            'Sport-specific cues:',
            $sportGuidance,
            'Observability notes for this upload:',
            $observabilityNotes,
            'Required output reminders:',
            '- criteria_evidence must explain the visible evidence for each criterion in one short sentence',
            '- limitations must list what prevents stronger certainty',
            '- If endurance is not truly visible, keep it conservative and say so',
            '- If the sequence is short, confidence should not be high',
        ];

        return implode("\n", $parts);
    }

    private static function buildFrameCaption(array $frame, int $index, int $frameCount): string {
        $parts = [
            'Frame ' . ($index + 1) . '/' . $frameCount,
            'role=' . ($frame['role'] ?? self::defaultFrameRole($index, $frameCount)),
        ];

        if ($frame['timestamp'] !== null) {
            $parts[] = 'timestamp=' . number_format((float) $frame['timestamp'], 2, '.', '') . 's';
        }

        if ($frame['ratio'] !== null) {
            $parts[] = 'timeline=' . (int) round(((float) $frame['ratio']) * 100) . '%';
        }

        if ($frame['motion'] !== null) {
            $parts[] = 'motion=' . number_format((float) $frame['motion'], 2, '.', '');
        }

        if ($frame['sharpness'] !== null) {
            $parts[] = 'sharpness=' . number_format((float) $frame['sharpness'], 2, '.', '');
        }

        if ($frame['brightness'] !== null) {
            $parts[] = 'brightness=' . number_format((float) $frame['brightness'], 2, '.', '');
        }

        return implode(' | ', $parts);
    }

    private static function responseSchema(): array {
        $criteriaProperties = [];
        $criteriaEvidenceProperties = [];

        foreach (CRITERIA as $criterion) {
            $criteriaProperties[$criterion] = [
                'type' => 'integer',
                'minimum' => 0,
                'maximum' => 100,
            ];
            $criteriaEvidenceProperties[$criterion] = [
                'type' => 'string',
            ];
        }

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'score_global' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'confidence' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'maximum' => 100,
                ],
                'evidence_quality' => [
                    'type' => 'string',
                    'enum' => ['strong', 'moderate', 'limited'],
                ],
                'summary' => [
                    'type' => 'string',
                ],
                'talent_signal' => [
                    'type' => 'string',
                    'enum' => ['high', 'medium', 'emerging'],
                ],
                'criteria' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => $criteriaProperties,
                    'required' => CRITERIA,
                ],
                'criteria_evidence' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => $criteriaEvidenceProperties,
                    'required' => CRITERIA,
                ],
                'strengths' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'improvements' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'coach_recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'recruiter_highlights' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'limitations' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'score_global',
                'confidence',
                'evidence_quality',
                'summary',
                'talent_signal',
                'criteria',
                'criteria_evidence',
                'strengths',
                'improvements',
                'coach_recommendations',
                'recruiter_highlights',
                'limitations',
            ],
        ];
    }

    private static function extractJsonText(array $response): ?string {
        if (!empty($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        foreach ($response['output'] ?? [] as $outputItem) {
            foreach ($outputItem['content'] ?? [] as $contentItem) {
                if (($contentItem['type'] ?? '') === 'output_text' && is_string($contentItem['text'] ?? null)) {
                    return $contentItem['text'];
                }
            }
        }

        return null;
    }

    private static function normalizeAnalysis(array $analysis, array $context): array {
        $criteria = [];

        foreach (CRITERIA as $criterion) {
            $criteria[$criterion] = self::clampScore((int) ($analysis['criteria'][$criterion] ?? 0));
        }

        $evidenceQuality = self::normalizeEvidenceQuality((string) ($analysis['evidence_quality'] ?? 'limited'));
        $criteriaEvidence = self::sanitizeCriteriaEvidence($analysis['criteria_evidence'] ?? []);
        $limitations = self::sanitizeList($analysis['limitations'] ?? []);
        $criteria = self::rebalanceCriteria($criteria, $criteriaEvidence, $context, $evidenceQuality);

        $summary = trim((string) ($analysis['summary'] ?? ''));
        $averageScore = (int) round(array_sum($criteria) / max(count($criteria), 1));
        $scoreGlobal = self::normalizeGlobalScore((int) ($analysis['score_global'] ?? $averageScore), $averageScore, $evidenceQuality);
        $confidence = self::calibrateConfidence(
            (int) ($analysis['confidence'] ?? 0),
            $evidenceQuality,
            $criteriaEvidence,
            $limitations,
            $context
        );

        if ($summary === '') {
            $studentName = $context['student']['name'] ?? 'Cet athlete';
            $summary = $studentName . ' montre un potentiel interessant, mais l analyse doit etre confirmee par plus de sequences video.';
        }

        return [
            'provider' => 'openai',
            'model' => OPENAI_MODEL,
            'score_global' => $scoreGlobal,
            'confidence' => $confidence,
            'evidence_quality' => $evidenceQuality,
            'talent_signal' => in_array($analysis['talent_signal'] ?? '', ['high', 'medium', 'emerging'], true)
                ? $analysis['talent_signal']
                : self::signalFromScore($scoreGlobal),
            'criteria' => $criteria,
            'criteria_evidence' => $criteriaEvidence,
            'summary' => $summary,
            'strengths' => self::sanitizeList($analysis['strengths'] ?? []),
            'improvements' => self::sanitizeList($analysis['improvements'] ?? []),
            'coach_recommendations' => self::sanitizeList($analysis['coach_recommendations'] ?? []),
            'recruiter_highlights' => self::sanitizeList($analysis['recruiter_highlights'] ?? []),
            'limitations' => $limitations,
            'analysis_error' => null,
        ];
    }

    private static function rebalanceCriteria(array $criteria, array $criteriaEvidence, array $context, string $evidenceQuality): array {
        $meta = $context['meta'] ?? [];
        $duration = (float) ($meta['duration'] ?? 0);
        $motionStats = self::frameSignalStats($context['frames'] ?? []);

        foreach (CRITERIA as $criterion) {
            $score = $criteria[$criterion] ?? 0;
            $evidenceScore = self::criterionEvidenceStrength($criteriaEvidence[$criterion] ?? '');

            if ($evidenceQuality === 'limited') {
                $score = (int) round(($score * 0.7) + 15);
            } elseif ($evidenceQuality === 'moderate') {
                $score = (int) round(($score * 0.85) + 7);
            }

            if ($evidenceScore < 0.35) {
                $score = (int) round(($score * 0.45) + 32);
            } elseif ($evidenceScore < 0.55) {
                $score = (int) round(($score * 0.7) + 16);
            }

            if ($criterion === 'endurance' && $duration > 0 && $duration < 8) {
                $score = min($score, 72);
            }

            if ($criterion === 'vitesse' && $motionStats['max_motion'] < 0.16) {
                $score = min($score, 74);
            }

            if ($criterion === 'force' && $motionStats['max_motion'] < 0.12) {
                $score = min($score, 73);
            }

            if ($criterion === 'souplesse' && $evidenceScore < 0.45) {
                $score = min($score, 76);
            }

            $criteria[$criterion] = self::clampScore($score);
        }

        return $criteria;
    }

    private static function normalizeGlobalScore(int $reportedScore, int $averageScore, string $evidenceQuality): int {
        $reportedScore = self::clampScore($reportedScore);
        $blendedScore = (int) round(($reportedScore + ($averageScore * 2)) / 3);

        if ($evidenceQuality === 'limited') {
            $blendedScore = min($blendedScore, $averageScore + 4);
        }

        return self::clampScore($blendedScore);
    }

    private static function calibrateConfidence(
        int $reportedConfidence,
        string $evidenceQuality,
        array $criteriaEvidence,
        array $limitations,
        array $context
    ): int {
        $confidence = self::clampScore($reportedConfidence > 0 ? $reportedConfidence : 62);
        $ceiling = match ($evidenceQuality) {
            'strong' => 90,
            'moderate' => 78,
            default => 62,
        };

        $meta = $context['meta'] ?? [];
        $duration = (float) ($meta['duration'] ?? 0);
        $frames = $context['frames'] ?? [];
        $weakCriteria = 0;

        foreach ($criteriaEvidence as $text) {
            if (self::criterionEvidenceStrength($text) < 0.55) {
                $weakCriteria += 1;
            }
        }

        $penalty = min(count($limitations) * 3, 12);

        if ($duration > 0 && $duration < 8) {
            $penalty += 8;
        }

        if ($duration > 0 && $duration < 4) {
            $penalty += 6;
        }

        if (count($frames) < 4) {
            $penalty += 6;
        }

        $confidence = min($confidence, $ceiling);
        $confidence -= $penalty;
        $confidence -= max(0, $weakCriteria - 1) * 4;

        return self::clampScore($confidence);
    }

    private static function sanitizeCriteriaEvidence($values): array {
        $result = [];

        foreach (CRITERIA as $criterion) {
            $text = trim((string) (is_array($values) ? ($values[$criterion] ?? '') : ''));
            $result[$criterion] = $text !== ''
                ? self::truncateText($text, 180)
                : 'Evidence visuelle limitee pour ce critere.';
        }

        return $result;
    }

    private static function normalizeEvidenceQuality(string $value): string {
        return in_array($value, ['strong', 'moderate', 'limited'], true) ? $value : 'limited';
    }

    private static function frameSignalStats(array $frames): array {
        $motionValues = [];
        $sharpnessValues = [];

        foreach ($frames as $frame) {
            if (!is_array($frame)) {
                continue;
            }

            if (isset($frame['motion'])) {
                $motionValues[] = max(0, min(1, (float) $frame['motion']));
            }

            if (isset($frame['sharpness'])) {
                $sharpnessValues[] = max(0, min(1, (float) $frame['sharpness']));
            }
        }

        return [
            'avg_motion' => $motionValues ? array_sum($motionValues) / count($motionValues) : 0,
            'max_motion' => $motionValues ? max($motionValues) : 0,
            'avg_sharpness' => $sharpnessValues ? array_sum($sharpnessValues) / count($sharpnessValues) : 0,
        ];
    }

    private static function criterionEvidenceStrength(string $text): float {
        $normalized = strtolower($text);

        if ($normalized === '') {
            return 0.2;
        }

        $weakSignals = [
            'non visible',
            'peu visible',
            'incertain',
            'difficile',
            'impossible',
            'limite',
            'ambigu',
            'court extrait',
            'a confirmer',
            'angle',
            'flou',
        ];

        $score = 0.9;

        foreach ($weakSignals as $signal) {
            if (str_contains($normalized, $signal)) {
                $score -= 0.12;
            }
        }

        return max(0.15, min(0.95, $score));
    }

    private static function buildFallbackAnalysis(array $context, ?string $errorMessage = null): array {
        $student = $context['student'] ?? [];
        $profile = strtolower(($student['sport'] ?? '') . ' ' . ($context['perf_type'] ?? ''));
        $seed = abs(crc32(
            ($student['name'] ?? 'atlas')
            . '|'
            . ($context['perf_type'] ?? '')
            . '|'
            . (string) (($context['meta']['duration'] ?? 0))
        ));

        $base = [
            'vitesse' => 72,
            'coordination' => 70,
            'endurance' => 69,
            'force' => 67,
            'souplesse' => 68,
        ];

        if (str_contains($profile, 'sprint') || str_contains($profile, 'athlet')) {
            $base = ['vitesse' => 86, 'coordination' => 76, 'endurance' => 78, 'force' => 74, 'souplesse' => 69];
        } elseif (str_contains($profile, 'football') || str_contains($profile, 'dribble')) {
            $base = ['vitesse' => 79, 'coordination' => 84, 'endurance' => 77, 'force' => 73, 'souplesse' => 70];
        } elseif (str_contains($profile, 'natation')) {
            $base = ['vitesse' => 78, 'coordination' => 77, 'endurance' => 84, 'force' => 71, 'souplesse' => 75];
        } elseif (str_contains($profile, 'gym')) {
            $base = ['vitesse' => 74, 'coordination' => 89, 'endurance' => 76, 'force' => 70, 'souplesse' => 88];
        }

        $criteria = [];
        $offsets = [3, 7, 11, 17, 23];

        foreach (array_values(CRITERIA) as $index => $criterion) {
            $variation = (($seed >> $offsets[$index]) % 9) - 4;
            $criteria[$criterion] = self::clampScore($base[$criterion] + $variation);
        }

        $scoreGlobal = self::clampScore((int) round(array_sum($criteria) / max(count($criteria), 1)));
        $signal = self::signalFromScore($scoreGlobal);
        $studentName = $student['name'] ?? 'Cet athlete';
        $sport = $student['sport'] ?? 'sport';
        $topCriterion = array_keys($criteria, max($criteria), true)[0] ?? 'coordination';
        $lowCriterion = array_keys($criteria, min($criteria), true)[0] ?? 'force';

        return [
            'provider' => 'demo',
            'model' => 'local-demo',
            'score_global' => $scoreGlobal,
            'confidence' => 54,
            'evidence_quality' => 'limited',
            'talent_signal' => $signal,
            'criteria' => $criteria,
            'criteria_evidence' => [
                'vitesse' => 'Indices partiels de vitesse visibles sur les images clefs.',
                'coordination' => 'Le controle corporel est estime a partir de quelques postures seulement.',
                'endurance' => 'La duree reste trop courte pour confirmer l endurance reellement.',
                'force' => 'La generation de puissance est seulement suggeree par la gestuelle.',
                'souplesse' => 'La mobilite apparente doit etre confirmee sur une sequence plus complete.',
            ],
            'summary' => $studentName . ' presente un profil ' . $signal . ' en ' . strtolower($sport) . ', avec un point fort en ' . $topCriterion . ' et une marge de progression en ' . $lowCriterion . '.',
            'strengths' => [
                'Le mouvement visible suggere une bonne qualite en ' . $topCriterion . '.',
                'Le rythme global parait coherent pour une premiere evaluation video.',
            ],
            'improvements' => [
                'Confirmer l analyse sur une video plus longue avec davantage d angles.',
                'Travailler prioritairement la composante ' . $lowCriterion . ' sur les prochaines seances.',
            ],
            'coach_recommendations' => [
                'Programmer une nouvelle capture video avec repere de distance ou de temps.',
                'Associer la prochaine evaluation a un objectif mesurable sur ' . $lowCriterion . '.',
            ],
            'recruiter_highlights' => [
                'Profil automatiquement prequalifie en mode demonstration.',
                'Evaluation a confirmer par une analyse OpenAI complete.',
            ],
            'limitations' => [
                'Analyse de demonstration basee sur un profil type et non sur une lecture vision complete.',
                'La confiance reste volontairement prudente sans validation OpenAI.',
            ],
            'analysis_error' => $errorMessage,
        ];
    }

    private static function sportSpecificGuidance(array $student, string $perfType): string {
        $profile = strtolower(trim(($student['sport'] ?? '') . ' ' . $perfType));

        if (str_contains($profile, 'sprint') || str_contains($profile, 'athlet')) {
            return '- Prioritize start mechanics, acceleration posture, stride intent, and lower-body power cues. Do not infer endurance from a short sprint.';
        }

        if (str_contains($profile, 'football') || str_contains($profile, 'dribble')) {
            return '- Prioritize first-step explosiveness, ball-body coordination, change of direction, balance on supports, and repeat-action clues only when visible.';
        }

        if (str_contains($profile, 'natation')) {
            return '- Prioritize body alignment, stroke rhythm, amplitude, fluidity, and propulsion clues. Be careful with force or endurance unless the sequence clearly supports them.';
        }

        if (str_contains($profile, 'gym')) {
            return '- Prioritize body tension, alignment, control on transitions, reception stability, amplitude, and mobility. Be conservative on endurance unless the routine duration supports it.';
        }

        if (str_contains($profile, 'saut') || str_contains($profile, 'lancer')) {
            return '- Prioritize approach mechanics, timing, force transfer, posture, and visible explosiveness. Do not invent measured performance.';
        }

        return '- Prioritize movement quality, control, visible explosiveness, and observable mobility. Avoid over-claiming sport-specific metrics that are not clearly shown.';
    }

    private static function observabilityNotes(array $context, array $frames): string {
        $meta = $context['meta'] ?? [];
        $duration = (float) ($meta['duration'] ?? 0);
        $stats = self::frameSignalStats($frames);
        $notes = [];

        if ($duration > 0 && $duration < 8) {
            $notes[] = '- Short clip: endurance evidence is limited.';
        }

        if ($stats['max_motion'] > 0 && $stats['max_motion'] < 0.16) {
            $notes[] = '- Motion between selected keyframes is modest; speed and force claims should stay cautious.';
        }

        if ($stats['avg_sharpness'] > 0 && $stats['avg_sharpness'] < 0.2) {
            $notes[] = '- Frame sharpness is limited; lower certainty if posture details are hard to see.';
        }

        if ($notes === []) {
            $notes[] = '- No automatic low-signal warning detected from the selected keyframes.';
        }

        return implode("\n", $notes);
    }

    private static function defaultFrameRole(int $index, int $frameCount): string {
        if ($index === 0) {
            return 'opening';
        }

        if ($index === max(0, $frameCount - 1)) {
            return 'closing';
        }

        return 'transition';
    }

    private static function sanitizeRole($value): string {
        $role = strtolower(trim((string) $value));
        return $role !== '' ? preg_replace('/[^a-z_ -]/', '', $role) : 'transition';
    }

    private static function sanitizeList(array $values): array {
        $cleaned = [];

        foreach ($values as $value) {
            $item = trim((string) $value);

            if ($item !== '') {
                $cleaned[] = self::truncateText($item, 180);
            }
        }

        return array_slice($cleaned, 0, 4);
    }

    private static function truncateText(string $text, int $maxLength): string {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        return rtrim(substr($text, 0, $maxLength - 3)) . '...';
    }

    private static function signalFromScore(int $score): string {
        if ($score >= 85) {
            return 'high';
        }

        if ($score >= 72) {
            return 'medium';
        }

        return 'emerging';
    }

    private static function clampScore(int $score): int {
        return max(0, min(100, $score));
    }
}
