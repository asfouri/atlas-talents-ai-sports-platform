<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/video_analysis_agent.php';

atlas_send_security_headers('api');
header('Content-Type: application/json');

Auth::init();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$publicActions = ['ping'];
$postActions = ['upload_video', 'chat_send', 'chat_read', 'favorite_toggle'];

function apiRespond(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function apiRequireRole(string ...$roles): array {
    if (!Auth::isLoggedIn()) {
        apiRespond(['success' => false, 'error' => 'Authentication required'], 401);
    }

    $user = Auth::user();

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        apiRespond(['success' => false, 'error' => 'Unauthorized'], 403);
    }

    return $user;
}

function apiCurrentStudents(array $user): array {
    return match ($user['role']) {
        ROLE_TEACHER => atlasGetTeacherStudents((int) $user['id']),
        ROLE_STUDENT => ($student = atlasGetLinkedStudentForUser($user)) ? [$student] : [],
        ROLE_MANAGER => atlasGetRecruiterStudents(),
        ROLE_RECRUITER => atlasGetRecruiterStudents(),
        ROLE_COACH => atlasGetCoachStudents((int) $user['id']),
        ROLE_ADMIN => atlasGetRecruiterStudents(),
        default => [],
    };
}

function apiCurrentStats(array $user): array {
    return match ($user['role']) {
        ROLE_TEACHER => atlasGetTeacherStats((int) $user['id']),
        ROLE_STUDENT => ($student = atlasGetLinkedStudentForUser($user)) ? [
            'students' => 1,
            'avg_score' => (int) ($student['score'] ?? 0),
            'talents' => (int) ((int) ($student['score'] ?? 0) >= 85),
            'videos' => (int) ($student['video_count'] ?? 0),
        ] : [],
        ROLE_MANAGER => [
            'students' => count(atlasGetRecruiterStudents()),
            'avg_score' => atlasAverageScore(atlasGetRecruiterStudents()),
            'talents' => count(atlasTopTalents(atlasGetRecruiterStudents())),
            'partners' => count(atlasGetChatDataForUser($user)['contacts'] ?? []),
        ],
        ROLE_RECRUITER => [
            'students' => count(atlasGetRecruiterStudents()),
            'avg_score' => atlasAverageScore(atlasGetRecruiterStudents()),
            'talents' => count(atlasTopTalents(atlasGetRecruiterStudents())),
        ],
        ROLE_COACH => [
            'students' => count(atlasGetCoachStudents((int) $user['id'])),
            'avg_score' => atlasAverageScore(atlasGetCoachStudents((int) $user['id'])),
            'talents' => count(atlasTopTalents(atlasGetCoachStudents((int) $user['id']))),
        ],
        ROLE_ADMIN => [
            'users' => count(atlasGetPlatformUsers()),
            'students' => count(atlasGetRecruiterStudents()),
            'avg_score' => atlasAverageScore(atlasGetRecruiterStudents()),
            'talents' => count(atlasTopTalents(atlasGetRecruiterStudents())),
        ],
        default => [],
    };
}

function apiProgress(array $user): array {
    $studentId = (int) ($_GET['student_id'] ?? $_POST['student_id'] ?? 0);

    if ($studentId === 0 && $user['role'] === ROLE_COACH) {
        $students = atlasGetCoachStudents((int) $user['id']);
        $studentId = (int) ($students[0]['id'] ?? 0);
    }

    if ($user['role'] === ROLE_STUDENT) {
        $student = atlasGetLinkedStudentForUser($user);
        $studentId = (int) ($student['id'] ?? 0);
    }

    return atlasGetCoachProgress($studentId ?: null);
}

function apiUploadVideo(): array {
    $user = apiRequireRole(ROLE_TEACHER);

    if ($GLOBALS['method'] !== 'POST') {
        apiRespond(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        apiRespond(['success' => false, 'error' => 'Jeton CSRF invalide.'], 419);
    }

    if (!Database::isAvailable()) {
        apiRespond(['success' => false, 'error' => 'MySQL doit etre configure pour enregistrer les videos et scores IA.'], 503);
    }

    $studentId = (int) ($_POST['student_id'] ?? 0);
    $perfType = trim((string) ($_POST['perf_type'] ?? ''));
    $student = atlasFindStudentForTeacher($studentId, (int) $user['id']);

    if (!$student) {
        apiRespond(['success' => false, 'error' => 'Eleve introuvable pour ce professeur.'], 404);
    }

    if ($perfType === '') {
        apiRespond(['success' => false, 'error' => 'Le type de performance est requis.'], 422);
    }

    if (empty($_FILES['video']) || !is_array($_FILES['video'])) {
        apiRespond(['success' => false, 'error' => 'Fichier video manquant.'], 422);
    }

    $file = $_FILES['video'];

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        apiRespond(['success' => false, 'error' => 'Echec de l’upload video.'], 422);
    }

    if ((int) ($file['size'] ?? 0) > UPLOAD_MAX_SIZE) {
        apiRespond(['success' => false, 'error' => 'La video depasse la taille maximale autorisee.'], 422);
    }

    $tmpName = $file['tmp_name'] ?? '';
    $originalName = (string) ($file['name'] ?? 'video.mp4');
    $mimeType = $file['type'] ?? '';
    $allowedMimeExtensions = [
        'video/mp4' => 'mp4',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
    ];

    if (!is_uploaded_file($tmpName)) {
        apiRespond(['success' => false, 'error' => 'Le fichier envoye est invalide.'], 422);
    }

    if (function_exists('mime_content_type')) {
        $mimeType = mime_content_type($tmpName) ?: $mimeType;
    } elseif (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? (finfo_file($finfo, $tmpName) ?: $mimeType) : $mimeType;
        if ($finfo) {
            finfo_close($finfo);
        }
    }

    if (!in_array($mimeType, ALLOWED_VIDEO_TYPES, true)) {
        apiRespond(['success' => false, 'error' => 'Format video non supporte. Utilisez MP4, MOV ou AVI.'], 422);
    }

    $extension = $allowedMimeExtensions[$mimeType] ?? null;

    if ($extension === null) {
        apiRespond(['success' => false, 'error' => 'Extension video non prise en charge.'], 422);
    }

    $frames = json_decode((string) ($_POST['frames'] ?? '[]'), true);

    if (!is_array($frames) || $frames === []) {
        apiRespond(['success' => false, 'error' => 'Aucune image cle n’a ete transmise pour l’analyse IA.'], 422);
    }

    $normalizedFrames = [];

    foreach (array_slice($frames, 0, AI_FRAME_LIMIT) as $frame) {
        if (is_string($frame) && str_starts_with($frame, 'data:image/')) {
            $normalizedFrames[] = [
                'image' => $frame,
            ];
            continue;
        }

        if (!is_array($frame)) {
            continue;
        }

        $image = (string) ($frame['image'] ?? $frame['data_url'] ?? '');

        if ($image === '' || !str_starts_with($image, 'data:image/')) {
            continue;
        }

        $normalizedFrames[] = [
            'image' => $image,
            'timestamp' => isset($frame['timestamp']) ? (float) $frame['timestamp'] : null,
            'ratio' => isset($frame['ratio']) ? (float) $frame['ratio'] : null,
            'motion' => isset($frame['motion']) ? (float) $frame['motion'] : null,
            'sharpness' => isset($frame['sharpness']) ? (float) $frame['sharpness'] : null,
            'brightness' => isset($frame['brightness']) ? (float) $frame['brightness'] : null,
            'role' => isset($frame['role']) ? trim((string) $frame['role']) : null,
        ];
    }

    if ($normalizedFrames === []) {
        apiRespond(['success' => false, 'error' => 'Les images extraites de la video sont invalides.'], 422);
    }

    $meta = json_decode((string) ($_POST['video_meta'] ?? '{}'), true);
    $meta = is_array($meta) ? $meta : [];

    $folder = date('Y/m');
    $targetDirectory = atlasEnsureUploadDirectory($folder);
    $safeBaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME)) ?: 'video';
    $storedFilename = strtolower($safeBaseName . '-' . uniqid() . '.' . $extension);
    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $storedFilename;
    $storagePath = trim($folder . '/' . $storedFilename, '/');

    if (!move_uploaded_file($tmpName, $targetPath)) {
        apiRespond(['success' => false, 'error' => 'Impossible d’enregistrer la video sur le serveur.'], 500);
    }

    $videoId = atlasCreateVideoRecord([
        'student_id' => $studentId,
        'teacher_id' => (int) $user['id'],
        'filename' => $storedFilename,
        'storage_path' => $storagePath,
        'original_name' => $originalName,
        'mime_type' => $mimeType,
        'file_size' => (int) ($file['size'] ?? 0),
        'duration_seconds' => isset($meta['duration']) ? (float) $meta['duration'] : null,
        'frame_count' => count($normalizedFrames),
        'perf_type' => $perfType,
        'ai_status' => 'processing',
    ]);

    try {
        $analysis = VideoAnalysisAgent::analyze([
            'student' => $student,
            'perf_type' => $perfType,
            'frames' => $normalizedFrames,
            'meta' => $meta,
        ]);

        atlasFinalizeVideoAnalysis($videoId, $analysis);

        return [
            'video' => atlasBuildVideoResponse($videoId),
            'stats' => atlasGetTeacherStats((int) $user['id']),
            'ai_enabled' => atlasAiEnabled(),
            'mode' => $analysis['provider'] ?? 'demo',
        ];
    } catch (Throwable $e) {
        atlasReportException($e, 'video-upload');
        atlasMarkVideoFailed($videoId, $e->getMessage());
        apiRespond(['success' => false, 'error' => atlasPublicErrorMessage('L analyse video a echoue. Veuillez reessayer.', $e)], 500);
    }
}

function apiChatBootstrap(): array {
    $user = apiRequireRole();
    return atlasGetChatDataForUser($user);
}

function apiChatSend(): array {
    $user = apiRequireRole();

    if ($GLOBALS['method'] !== 'POST') {
        apiRespond(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        apiRespond(['success' => false, 'error' => 'Jeton CSRF invalide.'], 419);
    }

    $recipientId = (int) ($_POST['recipient_id'] ?? 0);
    $body = (string) ($_POST['body'] ?? '');
    $studentId = isset($_POST['student_id']) && $_POST['student_id'] !== ''
        ? (int) $_POST['student_id']
        : null;

    atlasSendChatMessage($user, $recipientId, $body, $studentId);

    return atlasGetChatDataForUser($user);
}

function apiChatRead(): array {
    $user = apiRequireRole();

    if ($GLOBALS['method'] !== 'POST') {
        apiRespond(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        apiRespond(['success' => false, 'error' => 'Jeton CSRF invalide.'], 419);
    }

    $contactId = (int) ($_POST['contact_id'] ?? 0);
    atlasMarkConversationRead((int) ($user['id'] ?? 0), $contactId);

    return atlasGetChatDataForUser($user);
}

function apiFavorites(): array {
    $user = apiRequireRole(ROLE_RECRUITER);

    return [
        'student_ids' => atlasGetFavoriteStudentIdsForRecruiter((int) $user['id']),
        'students' => atlasGetFavoriteStudentsForRecruiter((int) $user['id']),
    ];
}

function apiFavoriteToggle(): array {
    $user = apiRequireRole(ROLE_RECRUITER);

    if ($GLOBALS['method'] !== 'POST') {
        apiRespond(['success' => false, 'error' => 'Method not allowed'], 405);
    }

    if (!Auth::verifyCsrf($_POST['_token'] ?? '')) {
        apiRespond(['success' => false, 'error' => 'Jeton CSRF invalide.'], 419);
    }

    $studentId = (int) ($_POST['student_id'] ?? 0);
    $favorite = filter_var($_POST['favorite'] ?? '', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    $result = atlasSetRecruiterFavorite((int) $user['id'], $studentId, $favorite ?? true);

    if (empty($result['success'])) {
        apiRespond(['success' => false, 'error' => (string) ($result['message'] ?? 'Operation impossible.')], 422);
    }

    return [
        'favorite' => !empty($result['favorite']),
        'favorites' => [
            'student_ids' => atlasGetFavoriteStudentIdsForRecruiter((int) $user['id']),
            'students' => atlasGetFavoriteStudentsForRecruiter((int) $user['id']),
        ],
    ];
}

$handlers = [
    'ping' => fn() => ['status' => 'ok', 'timestamp' => time()],
    'students' => fn() => apiCurrentStudents(apiRequireRole()),
    'progress' => fn() => apiProgress(apiRequireRole(ROLE_STUDENT, ROLE_COACH, ROLE_MANAGER, ROLE_ADMIN)),
    'stats' => fn() => apiCurrentStats(apiRequireRole()),
    'favorites' => fn() => apiFavorites(),
    'favorite_toggle' => fn() => apiFavoriteToggle(),
    'chat_bootstrap' => fn() => apiChatBootstrap(),
    'chat_send' => fn() => apiChatSend(),
    'chat_read' => fn() => apiChatRead(),
    'upload_video' => fn() => apiUploadVideo(),
];

if (!isset($handlers[$action])) {
    apiRespond(['success' => false, 'error' => 'Action not found'], 404);
}

if (in_array($action, $postActions, true) && $method !== 'POST') {
    apiRespond(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!in_array($action, $publicActions, true) && !Auth::isLoggedIn()) {
    apiRespond(['success' => false, 'error' => 'Authentication required'], 401);
}

try {
    apiRespond(['success' => true, 'data' => $handlers[$action]()]);
} catch (Throwable $e) {
    atlasReportException($e, 'api');
    apiRespond(['success' => false, 'error' => atlasPublicErrorMessage('Une erreur serveur est survenue.', $e)], 500);
}
