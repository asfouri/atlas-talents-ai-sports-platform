<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

function atlasDemoModeEnabled(): bool {
    return APP_ALLOW_DEMO_MODE || atlasOwnerDemoAccessActive() || atlasIsLocalRequest();
}

function atlasDebugEnabled(): bool {
    return APP_DEBUG;
}

function atlasIsLocalRequest(): bool {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    return preg_match('/\A(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?\z/', $host) === 1;
}

function atlasOwnerDemoAccessConfigured(): bool {
    return trim((string) APP_DEMO_ACCESS_SECRET) !== '';
}

function atlasOwnerDemoAccessActive(): bool {
    return session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['owner_demo_access']);
}

function atlasOwnerDemoAccessGrant(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION['owner_demo_access'] = 1;
}

function atlasOwnerDemoAccessRevoke(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    unset($_SESSION['owner_demo_access']);
}

function atlasOwnerDemoSecretMatches(?string $secret): bool {
    $configured = trim((string) APP_DEMO_ACCESS_SECRET);

    if ($configured === '' || $secret === null) {
        return false;
    }

    return hash_equals($configured, trim((string) $secret));
}

function atlasAllowedPublicRegistrationRoles(): array {
    $rawRoles = preg_split('/\s*,\s*/', strtolower((string) APP_ALLOWED_PUBLIC_REGISTRATION_ROLES)) ?: [];
    $allowed = [];
    $supported = [ROLE_TEACHER, ROLE_MANAGER, ROLE_RECRUITER, ROLE_COACH];

    foreach ($rawRoles as $role) {
        $role = atlasCanonicalRole((string) $role);

        if (in_array($role, $supported, true)) {
            $allowed[$role] = $role;
        }
    }

    if ($allowed === []) {
        $allowed[ROLE_TEACHER] = ROLE_TEACHER;
    }

    return array_values($allowed);
}

function atlasReportException(Throwable $throwable, string $context = 'Atlas Talents'): void {
    error_log(sprintf(
        '[%s] %s in %s:%d',
        $context,
        $throwable->getMessage(),
        $throwable->getFile(),
        $throwable->getLine()
    ));
}

function atlasPublicErrorMessage(string $genericMessage, Throwable $throwable): string {
    return atlasDebugEnabled() ? $throwable->getMessage() : $genericMessage;
}

function atlasAiEnabled(): bool {
    return OPENAI_API_KEY !== '';
}

function atlasEnsureUploadDirectory(string $relativePath = ''): string {
    $path = rtrim(UPLOAD_DIR, '\\/');

    if ($relativePath !== '') {
        $path .= DIRECTORY_SEPARATOR . trim($relativePath, '\\/');
    }

    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Impossible de creer le repertoire de stockage.');
    }

    return $path;
}

function atlasEnsurePrivateDataDirectory(string $relativePath = ''): string {
    $path = rtrim(PRIVATE_DATA_DIR, '\\/');

    if ($relativePath !== '') {
        $path .= DIRECTORY_SEPARATOR . trim($relativePath, '\\/');
    }

    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Impossible de creer le repertoire prive.');
    }

    return $path;
}

function atlasLegacyPublicVideoUrl(?string $storagePath): ?string {
    if (!$storagePath) {
        return null;
    }

    return APP_URL . '/public/uploads/' . ltrim(str_replace('\\', '/', $storagePath), '/');
}

function atlasVideoPublicUrl(?string $storagePath, ?int $videoId = null): ?string {
    if ($videoId && $videoId > 0) {
        return APP_URL . '/media.php?video_id=' . $videoId;
    }

    return atlasLegacyPublicVideoUrl($storagePath);
}

function atlasDemoSampleVideoUrls(): array {
    static $urls = null;

    if ($urls !== null) {
        return $urls;
    }

    if (!atlasDemoModeEnabled()) {
        $urls = [];
        return $urls;
    }

    $files = glob(APP_ROOT . '/public/uploads/*/*/*.mp4') ?: [];
    sort($files);
    $urls = [];

    foreach ($files as $file) {
        $relative = str_replace('\\', '/', substr($file, strlen(APP_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR)));
        $url = atlasVideoPublicUrl($relative);

        if ($url) {
            $urls[] = $url;
        }
    }

    return $urls;
}

function atlasEnsureDatabaseSchema(): void {
    static $ensured = false;

    if ($ensured || !Database::isAvailable()) {
        return;
    }

    $ensured = true;
}

function atlasCanonicalRole(string $role): string {
    $role = strtolower(trim($role));

    return match ($role) {
        ROLE_ADMIN => ROLE_MANAGER,
        default => $role,
    };
}

function atlasRoleLookupVariants(string $role): array {
    $role = atlasCanonicalRole($role);

    return match ($role) {
        ROLE_MANAGER => [ROLE_MANAGER, ROLE_ADMIN],
        default => $role !== '' ? [$role] : [],
    };
}

function atlasRoleLabel(string $role, bool $short = false): string {
    return match (atlasCanonicalRole($role)) {
        ROLE_TEACHER => $short ? 'Prof EPS' : 'Professeur d\'EPS',
        ROLE_STUDENT => 'Eleve',
        ROLE_MANAGER => $short ? 'Manager' : 'Manager de recrutement',
        ROLE_RECRUITER => $short ? 'Recruteur' : 'Recruteur / Club',
        ROLE_COACH => 'Coach sportif',
        default => 'Utilisateur',
    };
}

function atlasDashboardSegment(string $role): string {
    return match (atlasCanonicalRole($role)) {
        ROLE_TEACHER => ROLE_TEACHER,
        ROLE_STUDENT => ROLE_STUDENT,
        ROLE_MANAGER => ROLE_MANAGER,
        ROLE_RECRUITER => ROLE_RECRUITER,
        ROLE_COACH => ROLE_COACH,
        default => '',
    };
}

function atlasDashboardUrlForRole(string $role): string {
    $segment = atlasDashboardSegment($role);
    return $segment !== '' ? APP_URL . '/pages/' . $segment . '/dashboard.php' : APP_URL . '/index.php';
}

function atlasRoleAvatarClass(string $role): string {
    return match (atlasCanonicalRole($role)) {
        ROLE_RECRUITER => 'avatar-blue',
        ROLE_COACH => 'avatar-green',
        ROLE_MANAGER => 'avatar-purple',
        ROLE_STUDENT => 'avatar-gold',
        default => 'avatar-red',
    };
}

function atlasEnsureRoleSchema(): void {
    static $ensured = false;

    if ($ensured || !Database::isAvailable()) {
        return;
    }

    $ensured = true;
}

function getDemoUsers(): array {
    static $users = null;

    if ($users !== null) {
        return $users;
    }

    if (!atlasDemoModeEnabled()) {
        $users = [];
        return $users;
    }

    $hash = '$2y$12$p45/3AGsRj98Rnyd2YWSu.naMngg88BHxFlmHenboz5iPkl3qbYPO';

    $users = [
        [
            'id' => 1,
            'name' => 'Prof. Hassan Alami',
            'email' => 'teacher@demo.com',
            'password' => $hash,
            'role' => ROLE_TEACHER,
            'club' => '3eme College · Classe A',
            'ville' => 'Casablanca',
        ],
        [
            'id' => 2,
            'name' => 'Karim Recruteur',
            'email' => 'recruiter@demo.com',
            'password' => $hash,
            'role' => ROLE_RECRUITER,
            'club' => 'Raja Club Athletic',
            'ville' => 'Casablanca',
        ],
        [
            'id' => 3,
            'name' => 'Coach Ahmed',
            'email' => 'coach@demo.com',
            'password' => $hash,
            'role' => ROLE_COACH,
            'club' => 'AS FAR Rabat',
            'ville' => 'Rabat',
        ],
        [
            'id' => 4,
            'name' => 'Admin Atlas',
            'email' => 'admin@demo.com',
            'password' => $hash,
            'role' => ROLE_ADMIN,
            'club' => 'Atlas Scout Club',
            'ville' => 'Casablanca',
        ],
    ];

    $users[] = [
        'id' => 5,
        'name' => 'Meryem Manager',
        'email' => 'manager@demo.com',
        'password' => $hash,
        'role' => ROLE_MANAGER,
        'club' => 'Atlas Scout Club',
        'ville' => 'Casablanca',
        'student_id' => null,
    ];
    $users[] = [
        'id' => 6,
        'name' => 'Youssef El Amrani',
        'email' => 'student@demo.com',
        'password' => $hash,
        'role' => ROLE_STUDENT,
        'club' => 'College Al Massira',
        'ville' => 'Casablanca',
        'student_id' => 1,
    ];

    foreach ($users as $index => $user) {
        if (!array_key_exists('student_id', $user)) {
            $users[$index]['student_id'] = null;
        }
    }

    return $users;
}

function findDemoUser(string $email, string $role = ''): ?array {
    $lookupRoles = atlasRoleLookupVariants($role);

    foreach (getDemoUsers() as $user) {
        if (strcasecmp($user['email'], trim($email)) !== 0) {
            continue;
        }

        if ($lookupRoles !== [] && !in_array($user['role'], $lookupRoles, true)) {
            continue;
        }

        $user['role'] = atlasCanonicalRole((string) ($user['role'] ?? ''));
        return $user;
    }

    return null;
}

function scoreColor(int $score): string {
    if ($score >= 85) {
        return '#2E7D32';
    }
    if ($score >= 70) {
        return '#FF8F00';
    }
    return '#D93025';
}

function scoreBadgeClass(int $score): string {
    if ($score >= 85) {
        return 'badge-success';
    }
    if ($score >= 70) {
        return 'badge-warning';
    }
    return 'badge-danger';
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $initials = strtoupper(substr($parts[0] ?? 'U', 0, 1));

    if (count($parts) > 1) {
        $initials .= strtoupper(substr($parts[1], 0, 1));
    }

    return $initials;
}

function timeAgo(string $datetime): string {
    $diff = time() - strtotime($datetime);

    if ($diff < 60) {
        return 'a l’instant';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' min';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . 'h';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' jours';
    }

    return date('d/m/Y', strtotime($datetime));
}

function formatDate(string $date): string {
    $months = ['', 'jan', 'fev', 'mar', 'avr', 'mai', 'juin', 'juil', 'aout', 'sep', 'oct', 'nov', 'dec'];
    $timestamp = strtotime($date);

    return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)];
}

function formatLongDate(string $date = 'now', bool $withDayName = false): string {
    $timestamp = strtotime($date);
    $days = [
        'dimanche',
        'lundi',
        'mardi',
        'mercredi',
        'jeudi',
        'vendredi',
        'samedi',
    ];
    $months = [
        1 => 'janvier',
        2 => 'fevrier',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'aout',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'decembre',
    ];

    $parts = [];

    if ($withDayName) {
        $parts[] = $days[(int) date('w', $timestamp)];
    }

    $parts[] = date('j', $timestamp);
    $parts[] = $months[(int) date('n', $timestamp)];
    $parts[] = date('Y', $timestamp);

    return implode(' ', $parts);
}

function userDisplayName(array $user): string {
    return $user['name'] ?? 'Utilisateur';
}

function userSubtitle(array $user): string {
    if (!empty($user['club'])) {
        return $user['club'];
    }

    return match (atlasCanonicalRole((string) ($user['role'] ?? ''))) {
        ROLE_TEACHER => 'Professeur d\'EPS',
        ROLE_STUDENT => 'Espace eleve',
        ROLE_MANAGER => 'Cellule recrutement',
        ROLE_RECRUITER => 'Recruteur',
        ROLE_COACH => 'Coach sportif',
        default => 'Utilisateur',
    };
}

function getDemoStudents(): array {
    if (!atlasDemoModeEnabled()) {
        return [];
    }

    $sampleVideos = atlasDemoSampleVideoUrls();
    $students = [
        ['id' => 1, 'teacher_id' => 1, 'name' => 'Youssef El Amrani', 'age' => 14, 'ville' => 'Casablanca', 'sport' => 'Athletisme', 'score' => 87, 'vitesse' => 92, 'coordination' => 88, 'endurance' => 85, 'force' => 79, 'souplesse' => 82, 'perf_type' => 'Sprint', 'updated' => '2024-03-27 10:00:00', 'ai_status' => 'done', 'ai_summary' => 'Profil explosif avec un bon potentiel sur les epreuves de vitesse.', 'ai_confidence' => 82, 'analysis_provider' => 'demo', 'strengths_list' => ['Acceleration rapide', 'Bonne coordination sur les appuis'], 'improvements_list' => ['Renforcer la force maximale', 'Confirmer sur plusieurs prises'], 'recommendations_list' => ['Maintenir les sessions de sprint', 'Ajouter du renforcement court'], 'recruiter_highlights_list' => ['Potentiel vitesse eleve', 'Profil deja visible aux clubs'], 'video_count' => 4],
        ['id' => 2, 'teacher_id' => 1, 'name' => 'Fatima Zahra Bennani', 'age' => 15, 'ville' => 'Rabat', 'sport' => 'Gymnastique', 'score' => 91, 'vitesse' => 85, 'coordination' => 94, 'endurance' => 88, 'force' => 81, 'souplesse' => 91, 'perf_type' => 'Gym artistique', 'updated' => '2024-03-22 14:00:00', 'ai_status' => 'done', 'ai_summary' => 'Excellente maitrise technique et grande qualite corporelle.', 'ai_confidence' => 86, 'analysis_provider' => 'demo', 'strengths_list' => ['Coordination tres solide', 'Souplesse remarquable'], 'improvements_list' => ['Poursuivre la puissance explosive', 'Stabiliser les receptions'], 'recommendations_list' => ['Conserver le volume technique', 'Ajouter du gainage dynamique'], 'recruiter_highlights_list' => ['Profil tres complet', 'Execution propre'], 'video_count' => 3],
        ['id' => 3, 'teacher_id' => 1, 'name' => 'Amine Ouali', 'age' => 13, 'ville' => 'Marrakech', 'sport' => 'Football', 'score' => 73, 'vitesse' => 76, 'coordination' => 70, 'endurance' => 78, 'force' => 72, 'souplesse' => 69, 'perf_type' => 'Dribble', 'updated' => '2024-03-18 09:00:00', 'ai_status' => 'done', 'ai_summary' => 'Bon potentiel technique, encore inegal sur l’intensite globale.', 'ai_confidence' => 76, 'analysis_provider' => 'demo', 'strengths_list' => ['Gestuelle prometteuse', 'Bonne disponibilite physique'], 'improvements_list' => ['Fluidifier les changements de direction', 'Mieux enchainer les efforts'], 'recommendations_list' => ['Travail de coordination balle-pied', 'Jeux reduits haute intensite'], 'recruiter_highlights_list' => ['Jeune profil a suivre'], 'video_count' => 2],
        ['id' => 4, 'teacher_id' => 1, 'name' => 'Layla El Fassi', 'age' => 14, 'ville' => 'Fes', 'sport' => 'Natation', 'score' => 86, 'vitesse' => 87, 'coordination' => 83, 'endurance' => 92, 'force' => 78, 'souplesse' => 88, 'perf_type' => 'Nage libre', 'updated' => '2024-03-24 16:00:00', 'ai_status' => 'done', 'ai_summary' => 'Base aerobie forte et technique reguliere sur le mouvement observe.', 'ai_confidence' => 81, 'analysis_provider' => 'demo', 'strengths_list' => ['Endurance tres interessante', 'Souplesse utile a la glisse'], 'improvements_list' => ['Mieux transferer la force', 'Completer avec une vue laterale'], 'recommendations_list' => ['Serie seuil + technique', 'Travail de puissance hors eau'], 'recruiter_highlights_list' => ['Profil endurant et regulier'], 'video_count' => 3],
        ['id' => 5, 'teacher_id' => 1, 'name' => 'Khalid Mansouri', 'age' => 16, 'ville' => 'Marrakech', 'sport' => 'Football', 'score' => 86, 'vitesse' => 89, 'coordination' => 86, 'endurance' => 90, 'force' => 84, 'souplesse' => 80, 'perf_type' => 'Match football', 'updated' => '2024-03-24 08:00:00', 'ai_status' => 'done', 'ai_summary' => 'Volume de course solide et bon engagement dans les actions.', 'ai_confidence' => 80, 'analysis_provider' => 'demo', 'strengths_list' => ['Vitesse de projection', 'Endurance competitive'], 'improvements_list' => ['Affiner la mobilite', 'Isoler la force sur appuis'], 'recommendations_list' => ['Travail de sprint repete', 'Mobilite hanche/cheville'], 'recruiter_highlights_list' => ['Profil deja competitif'], 'video_count' => 5],
        ['id' => 6, 'teacher_id' => 1, 'name' => 'Sara Idrissi', 'age' => 14, 'ville' => 'Fes', 'sport' => 'Natation', 'score' => 84, 'vitesse' => 86, 'coordination' => 82, 'endurance' => 90, 'force' => 76, 'souplesse' => 85, 'perf_type' => 'Nage papillon', 'updated' => '2024-03-22 11:00:00', 'ai_status' => 'done', 'ai_summary' => 'Profil regulier avec une base physiologique solide pour progresser vite.', 'ai_confidence' => 79, 'analysis_provider' => 'demo', 'strengths_list' => ['Endurance elevee', 'Bonne tenue technique'], 'improvements_list' => ['Renforcer la puissance', 'Mesurer la cadence reellement'], 'recommendations_list' => ['Travail technique specifique', 'Cycle de force courte'], 'recruiter_highlights_list' => ['Bonne marge de progression'], 'video_count' => 4],
    ];

    foreach ($students as $index => $student) {
        if ($sampleVideos !== []) {
            $students[$index]['video_url'] = $sampleVideos[$index % count($sampleVideos)];
        } else {
            $students[$index]['video_url'] = null;
        }
    }

    return $students;
}

function getDemoProgress(): array {
    return [
        'labels' => ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin'],
        'vitesse' => [65, 70, 77, 84, 88, 92],
        'coordination' => [72, 76, 80, 84, 87, 88],
        'endurance' => [68, 72, 76, 80, 83, 85],
        'force' => [75, 78, 78, 79, 80, 79],
        'souplesse' => [70, 74, 78, 80, 81, 82],
        'global' => [66, 72, 78, 83, 87, 90],
    ];
}

function atlasEmptyProgress(): array {
    return [
        'labels' => [],
        'vitesse' => [],
        'coordination' => [],
        'endurance' => [],
        'force' => [],
        'souplesse' => [],
        'global' => [],
    ];
}

function atlasExplodeLines(?string $value): array {
    if (!$value) {
        return [];
    }

    $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $cleaned = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line !== '') {
            $cleaned[] = $line;
        }
    }

    return $cleaned;
}

function atlasImplodeLines(array $values): string {
    $cleaned = [];

    foreach ($values as $value) {
        $value = trim((string) $value);

        if ($value !== '') {
            $cleaned[] = $value;
        }
    }

    return implode("\n", $cleaned);
}

function atlasHydrateStudentRow(array $row): array {
    $score = isset($row['score']) ? (int) $row['score'] : (int) ($row['score_global'] ?? 0);
    $updated = $row['updated'] ?? $row['analyzed_at'] ?? $row['created_at'] ?? date('Y-m-d H:i:s');
    $videoCount = isset($row['video_count']) ? (int) $row['video_count'] : 0;

    $row['score'] = $score;
    $row['vitesse'] = (int) ($row['vitesse'] ?? 0);
    $row['coordination'] = (int) ($row['coordination'] ?? 0);
    $row['endurance'] = (int) ($row['endurance'] ?? 0);
    $row['force'] = (int) ($row['force'] ?? 0);
    $row['souplesse'] = (int) ($row['souplesse'] ?? 0);
    $row['updated'] = $updated;
    $row['video_count'] = $videoCount;
    $row['video_id'] = isset($row['video_id']) ? (int) $row['video_id'] : null;
    $row['ai_confidence'] = isset($row['ai_confidence']) ? (int) $row['ai_confidence'] : null;
    $row['strengths_list'] = isset($row['strengths_list']) ? (array) $row['strengths_list'] : atlasExplodeLines($row['ai_strengths'] ?? '');
    $row['improvements_list'] = isset($row['improvements_list']) ? (array) $row['improvements_list'] : atlasExplodeLines($row['ai_improvements'] ?? '');
    $row['recommendations_list'] = isset($row['recommendations_list']) ? (array) $row['recommendations_list'] : atlasExplodeLines($row['ai_recommendations'] ?? '');
    $row['recruiter_highlights_list'] = isset($row['recruiter_highlights_list']) ? (array) $row['recruiter_highlights_list'] : atlasExplodeLines($row['recruiter_highlights'] ?? '');
    $row['video_url'] = atlasVideoPublicUrl($row['storage_path'] ?? null, $row['video_id'] ?? null) ?? ($row['video_url'] ?? null);
    $row['talent_signal'] = $row['talent_signal'] ?? ($score >= 85 ? 'high' : ($score >= 72 ? 'medium' : 'emerging'));

    return $row;
}

function atlasFetchStudentsWithLatestVideo(string $whereSql = '', array $params = []): array {
    atlasEnsureDatabaseSchema();

    if (!Database::isAvailable()) {
        return [];
    }

    $sql = "
        SELECT
            s.id,
            s.teacher_id,
            s.name,
            s.age,
            s.ville,
            s.sport,
            s.school,
            s.avatar_url,
            latest.id AS video_id,
            latest.perf_type,
            latest.vitesse,
            latest.coordination,
            latest.endurance,
            latest.`force`,
            latest.souplesse,
            latest.score_global AS score,
            latest.ai_status,
            latest.ai_summary,
            latest.ai_confidence,
            latest.analysis_provider,
            latest.ai_strengths,
            latest.ai_improvements,
            latest.ai_recommendations,
            latest.recruiter_highlights,
            latest.storage_path,
            latest.analyzed_at,
            latest.created_at AS video_created_at,
            COALESCE(latest.analyzed_at, latest.created_at, s.created_at) AS updated,
            (
                SELECT COUNT(*)
                FROM videos vc
                WHERE vc.student_id = s.id
            ) AS video_count
        FROM students s
        LEFT JOIN videos latest ON latest.id = (
            SELECT v2.id
            FROM videos v2
            WHERE v2.student_id = s.id
            ORDER BY COALESCE(v2.analyzed_at, v2.created_at) DESC, v2.id DESC
            LIMIT 1
        )
        WHERE s.is_active = 1
        " . $whereSql . "
        ORDER BY COALESCE(latest.analyzed_at, latest.created_at, s.created_at) DESC, s.name ASC
    ";

    return array_map('atlasHydrateStudentRow', Database::fetchAll($sql, $params));
}

function atlasGetTeacherStudents(int $teacherId): array {
    if (Database::isAvailable()) {
        return atlasFetchStudentsWithLatestVideo(' AND s.teacher_id = ? ', [$teacherId]);
    }

    return array_values(array_filter(getDemoStudents(), fn(array $student): bool => (int) $student['teacher_id'] === $teacherId));
}

function atlasGetRecruiterStudents(): array {
    if (Database::isAvailable()) {
        return atlasFetchStudentsWithLatestVideo(' AND latest.id IS NOT NULL AND latest.ai_status = ? ', ['done']);
    }

    return getDemoStudents();
}

function atlasGetCoachStudents(int $coachId): array {
    if (!Database::isAvailable()) {
        $assignedStudentIds = array_values(array_map(
            static fn(array $assignment): int => (int) ($assignment['student_id'] ?? 0),
            array_filter(atlasGetCoachAssignments(), static fn(array $assignment): bool => (int) ($assignment['coach_id'] ?? 0) === $coachId)
        ));

        if ($assignedStudentIds === []) {
            return [];
        }

        return array_values(array_filter(getDemoStudents(), static function (array $student) use ($assignedStudentIds): bool {
            return in_array((int) ($student['id'] ?? 0), $assignedStudentIds, true);
        }));
    }

    atlasEnsureCoachAssignmentsSchema();
    $assignments = Database::fetchAll(
        "SELECT student_id FROM coach_students WHERE coach_id = ?",
        [$coachId]
    );

    if ($assignments === []) {
        return [];
    }

    $studentIds = array_column($assignments, 'student_id');
    $placeholders = implode(', ', array_fill(0, count($studentIds), '?'));

    return atlasFetchStudentsWithLatestVideo(' AND s.id IN (' . $placeholders . ') ', $studentIds);
}

function atlasGetAllStudents(): array {
    if (Database::isAvailable()) {
        return atlasFetchStudentsWithLatestVideo();
    }

    return getDemoStudents();
}

function atlasGetStudentById(int $studentId): ?array {
    if ($studentId <= 0) {
        return null;
    }

    if (Database::isAvailable()) {
        $rows = atlasFetchStudentsWithLatestVideo(' AND s.id = ? ', [$studentId]);
        return $rows[0] ?? null;
    }

    foreach (getDemoStudents() as $student) {
        if ((int) ($student['id'] ?? 0) === $studentId) {
            return atlasHydrateStudentRow($student);
        }
    }

    return null;
}

function atlasGetLinkedStudentForUser(array $user): ?array {
    $studentId = (int) ($user['student_id'] ?? 0);

    if ($studentId > 0) {
        return atlasGetStudentById($studentId);
    }

    if (!Database::isAvailable()) {
        foreach (getDemoUsers() as $demoUser) {
            if ((int) ($demoUser['id'] ?? 0) === (int) ($user['id'] ?? 0) && !empty($demoUser['student_id'])) {
                return atlasGetStudentById((int) $demoUser['student_id']);
            }
        }
    }

    return null;
}

function atlasAverageScore(array $students): int {
    if ($students === []) {
        return 0;
    }

    return (int) round(array_sum(array_column($students, 'score')) / count($students));
}

function atlasTopTalents(array $students, int $threshold = 85): array {
    return array_values(array_filter($students, fn(array $student): bool => (int) ($student['score'] ?? 0) >= $threshold));
}

function atlasGetTeacherStats(int $teacherId): array {
    $students = atlasGetTeacherStudents($teacherId);

    if (!Database::isAvailable()) {
        return [
            'students' => count($students),
            'videos' => array_sum(array_column($students, 'video_count')),
            'avg_score' => atlasAverageScore($students),
            'talents' => count(atlasTopTalents($students)),
        ];
    }

    atlasEnsureDatabaseSchema();

    return [
        'students' => (int) Database::fetchOne("SELECT COUNT(*) AS total FROM students WHERE teacher_id = ? AND is_active = 1", [$teacherId])['total'],
        'videos' => (int) Database::fetchOne("SELECT COUNT(*) AS total FROM videos WHERE teacher_id = ?", [$teacherId])['total'],
        'avg_score' => (int) round((float) (Database::fetchOne("SELECT AVG(score_global) AS avg_score FROM videos WHERE teacher_id = ? AND ai_status = 'done'", [$teacherId])['avg_score'] ?? 0)),
        'talents' => count(atlasTopTalents($students)),
    ];
}

function atlasGetRecentVideosForTeacher(int $teacherId, int $limit = 4): array {
    if (!Database::isAvailable()) {
        $students = atlasGetTeacherStudents($teacherId);
        usort($students, fn(array $a, array $b): int => strcmp($b['updated'], $a['updated']));

        return array_slice(array_map(function (array $student): array {
            return [
                'student_name' => $student['name'],
                'perf_type' => $student['perf_type'] ?? 'Performance',
                'score_global' => $student['score'],
                'ai_status' => $student['ai_status'] ?? 'done',
                'analysis_provider' => $student['analysis_provider'] ?? 'demo',
                'created_at' => $student['updated'],
                'analyzed_at' => $student['updated'],
                'ai_summary' => $student['ai_summary'] ?? '',
            ];
        }, $students), 0, $limit);
    }

    atlasEnsureDatabaseSchema();

    return Database::fetchAll(
        "
        SELECT
            v.*,
            s.name AS student_name
        FROM videos v
        INNER JOIN students s ON s.id = v.student_id
        WHERE v.teacher_id = ?
        ORDER BY COALESCE(v.analyzed_at, v.created_at) DESC, v.id DESC
        LIMIT $limit
        ",
        [$teacherId]
    );
}

function atlasGetRecentVideosForStudent(int $studentId, int $limit = 4): array {
    if ($studentId <= 0) {
        return [];
    }

    if (!Database::isAvailable()) {
        $student = atlasGetStudentById($studentId);

        if (!$student) {
            return [];
        }

        return array_fill(0, max(1, min($limit, (int) ($student['video_count'] ?? 1))), [
            'student_name' => $student['name'],
            'perf_type' => $student['perf_type'] ?? 'Performance',
            'score_global' => $student['score'],
            'ai_status' => $student['ai_status'] ?? 'done',
            'analysis_provider' => $student['analysis_provider'] ?? 'demo',
            'created_at' => $student['updated'],
            'analyzed_at' => $student['updated'],
            'ai_summary' => $student['ai_summary'] ?? '',
        ]);
    }

    atlasEnsureDatabaseSchema();

    return Database::fetchAll(
        "
        SELECT
            v.*,
            s.name AS student_name
        FROM videos v
        INNER JOIN students s ON s.id = v.student_id
        WHERE v.student_id = ?
        ORDER BY COALESCE(v.analyzed_at, v.created_at) DESC, v.id DESC
        LIMIT $limit
        ",
        [$studentId]
    );
}

function atlasGetCoachProgress(?int $studentId = null, int $limit = 6): array {
    if (!$studentId) {
        return atlasEmptyProgress();
    }

    if (!Database::isAvailable()) {
        return atlasDemoModeEnabled() ? getDemoProgress() : atlasEmptyProgress();
    }

    atlasEnsureDatabaseSchema();

    $rows = Database::fetchAll(
        "
        SELECT created_at, vitesse, coordination, endurance, `force`, souplesse, score_global
        FROM videos
        WHERE student_id = ? AND ai_status = 'done'
        ORDER BY COALESCE(analyzed_at, created_at) DESC, id DESC
        LIMIT $limit
        ",
        [$studentId]
    );

    if ($rows === []) {
        return atlasEmptyProgress();
    }

    $rows = array_reverse($rows);

    return [
        'labels' => array_map(fn(array $row): string => formatDate($row['created_at']), $rows),
        'vitesse' => array_map(fn(array $row): int => (int) $row['vitesse'], $rows),
        'coordination' => array_map(fn(array $row): int => (int) $row['coordination'], $rows),
        'endurance' => array_map(fn(array $row): int => (int) $row['endurance'], $rows),
        'force' => array_map(fn(array $row): int => (int) $row['force'], $rows),
        'souplesse' => array_map(fn(array $row): int => (int) $row['souplesse'], $rows),
        'global' => array_map(fn(array $row): int => (int) $row['score_global'], $rows),
    ];
}

function atlasGetPlatformUsers(): array {
    if (!Database::isAvailable()) {
        return array_map(function (array $user): array {
            $user['role'] = atlasCanonicalRole((string) ($user['role'] ?? ''));
            return $user;
        }, getDemoUsers());
    }

    atlasEnsureRoleSchema();

    return array_map(function (array $user): array {
        $user['role'] = atlasCanonicalRole((string) ($user['role'] ?? ''));
        $user['student_id'] = isset($user['student_id']) ? (int) $user['student_id'] : null;
        return $user;
    }, Database::fetchAll("SELECT id, name, email, role, club, ville, is_active, student_id FROM users ORDER BY created_at DESC"));
}

function atlasFindStudentForTeacher(int $studentId, int $teacherId): ?array {
    if (Database::isAvailable()) {
        atlasEnsureDatabaseSchema();
        $student = Database::fetchOne(
            "SELECT * FROM students WHERE id = ? AND teacher_id = ? AND is_active = 1",
            [$studentId, $teacherId]
        );

        if ($student) {
            return $student;
        }

        return null;
    }

    foreach (getDemoStudents() as $student) {
        if ((int) $student['id'] === $studentId && (int) $student['teacher_id'] === $teacherId) {
            return $student;
        }
    }

    return null;
}

function atlasCreateVideoRecord(array $payload): int {
    atlasEnsureDatabaseSchema();

    return Database::insert('videos', [
        'student_id' => $payload['student_id'],
        'teacher_id' => $payload['teacher_id'],
        'filename' => $payload['filename'] ?? null,
        'storage_path' => $payload['storage_path'] ?? null,
        'original_name' => $payload['original_name'] ?? null,
        'mime_type' => $payload['mime_type'] ?? null,
        'file_size' => $payload['file_size'] ?? null,
        'duration_seconds' => $payload['duration_seconds'] ?? null,
        'frame_count' => $payload['frame_count'] ?? null,
        'perf_type' => $payload['perf_type'] ?? null,
        'ai_status' => $payload['ai_status'] ?? 'pending',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

function atlasFinalizeVideoAnalysis(int $videoId, array $analysis, string $status = 'done'): void {
    atlasEnsureDatabaseSchema();

    Database::query(
        "
        UPDATE videos
        SET
            vitesse = :vitesse,
            coordination = :coordination,
            endurance = :endurance,
            `force` = :force,
            souplesse = :souplesse,
            score_global = :score_global,
            ai_status = :ai_status,
            analyzed_at = :analyzed_at,
            analysis_provider = :analysis_provider,
            ai_confidence = :ai_confidence,
            ai_summary = :ai_summary,
            ai_strengths = :ai_strengths,
            ai_improvements = :ai_improvements,
            ai_recommendations = :ai_recommendations,
            recruiter_highlights = :recruiter_highlights,
            analysis_payload = :analysis_payload,
            analysis_error = :analysis_error
        WHERE id = :video_id
        ",
        [
            'vitesse' => $analysis['criteria']['vitesse'] ?? 0,
            'coordination' => $analysis['criteria']['coordination'] ?? 0,
            'endurance' => $analysis['criteria']['endurance'] ?? 0,
            'force' => $analysis['criteria']['force'] ?? 0,
            'souplesse' => $analysis['criteria']['souplesse'] ?? 0,
            'score_global' => $analysis['score_global'] ?? 0,
            'ai_status' => $status,
            'analyzed_at' => date('Y-m-d H:i:s'),
            'analysis_provider' => $analysis['provider'] ?? 'demo',
            'ai_confidence' => $analysis['confidence'] ?? null,
            'ai_summary' => $analysis['summary'] ?? null,
            'ai_strengths' => atlasImplodeLines($analysis['strengths'] ?? []),
            'ai_improvements' => atlasImplodeLines($analysis['improvements'] ?? []),
            'ai_recommendations' => atlasImplodeLines($analysis['coach_recommendations'] ?? []),
            'recruiter_highlights' => atlasImplodeLines($analysis['recruiter_highlights'] ?? []),
            'analysis_payload' => json_encode($analysis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'analysis_error' => $analysis['analysis_error'] ?? null,
            'video_id' => $videoId,
        ]
    );
}

function atlasMarkVideoFailed(int $videoId, string $error): void {
    atlasEnsureDatabaseSchema();

    Database::query(
        "UPDATE videos SET ai_status = 'failed', analysis_error = ?, analyzed_at = ? WHERE id = ?",
        [$error, date('Y-m-d H:i:s'), $videoId]
    );
}

function atlasBuildVideoResponse(int $videoId): ?array {
    if (!Database::isAvailable()) {
        return null;
    }

    atlasEnsureDatabaseSchema();

    $video = Database::fetchOne(
        "
        SELECT v.*, s.name AS student_name, s.sport, s.age, s.ville
        FROM videos v
        INNER JOIN students s ON s.id = v.student_id
        WHERE v.id = ?
        ",
        [$videoId]
    );

    if (!$video) {
        return null;
    }

    $analysisPayload = json_decode((string) ($video['analysis_payload'] ?? ''), true);
    $analysisPayload = is_array($analysisPayload) ? $analysisPayload : [];
    $criteriaEvidence = [];

    foreach (CRITERIA as $criterion) {
        $criteriaEvidence[$criterion] = trim((string) (($analysisPayload['criteria_evidence'][$criterion] ?? '')));
    }

    return [
        'id' => (int) $video['id'],
        'student_id' => (int) $video['student_id'],
        'student_name' => $video['student_name'],
        'sport' => $video['sport'],
        'age' => isset($video['age']) ? (int) $video['age'] : null,
        'ville' => $video['ville'],
        'perf_type' => $video['perf_type'],
        'score_global' => (int) ($video['score_global'] ?? 0),
        'confidence' => isset($video['ai_confidence']) ? (int) $video['ai_confidence'] : null,
        'evidence_quality' => $analysisPayload['evidence_quality'] ?? null,
        'provider' => $video['analysis_provider'] ?? 'demo',
        'status' => $video['ai_status'],
        'summary' => $video['ai_summary'],
        'criteria' => [
            'vitesse' => (int) ($video['vitesse'] ?? 0),
            'coordination' => (int) ($video['coordination'] ?? 0),
            'endurance' => (int) ($video['endurance'] ?? 0),
            'force' => (int) ($video['force'] ?? 0),
            'souplesse' => (int) ($video['souplesse'] ?? 0),
        ],
        'strengths' => atlasExplodeLines($video['ai_strengths'] ?? ''),
        'improvements' => atlasExplodeLines($video['ai_improvements'] ?? ''),
        'coach_recommendations' => atlasExplodeLines($video['ai_recommendations'] ?? ''),
        'recruiter_highlights' => atlasExplodeLines($video['recruiter_highlights'] ?? ''),
        'criteria_evidence' => $criteriaEvidence,
        'limitations' => array_values(array_filter((array) ($analysisPayload['limitations'] ?? []), fn($value): bool => trim((string) $value) !== '')),
        'video_url' => atlasVideoPublicUrl($video['storage_path'] ?? null, (int) $video['id']),
        'analysis_error' => $video['analysis_error'] ?? null,
        'updated' => $video['analyzed_at'] ?? $video['created_at'],
    ];
}

function atlasGetVideoMediaRecord(int $videoId): ?array {
    if ($videoId <= 0 || !Database::isAvailable()) {
        return null;
    }

    atlasEnsureDatabaseSchema();

    $video = Database::fetchOne(
        "SELECT id, student_id, storage_path, original_name, mime_type, file_size
         FROM videos
         WHERE id = ?",
        [$videoId]
    );

    return $video ?: null;
}

function atlasResolveVideoFilePath(?string $storagePath): ?string {
    if (!$storagePath) {
        return null;
    }

    $normalized = ltrim(str_replace('\\', '/', $storagePath), '/');
    $candidates = [
        rtrim(UPLOAD_DIR, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized),
        rtrim(LEGACY_PUBLIC_UPLOAD_DIR, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized),
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function atlasCanAccessStudent(array $user, int $studentId): bool {
    if ($studentId <= 0) {
        return false;
    }

    return in_array($studentId, atlasGetRelevantStudentIdsForUser($user), true);
}

function atlasCanAccessVideo(array $user, int $videoId): bool {
    $video = atlasGetVideoMediaRecord($videoId);

    if (!$video) {
        return false;
    }

    return atlasCanAccessStudent($user, (int) ($video['student_id'] ?? 0));
}

function atlasEnsureChatSchema(): void {
    static $ensured = false;

    if ($ensured || !Database::isAvailable()) {
        return;
    }

    $ensured = true;
}

function atlasEnsureCoachAssignmentsSchema(): void {
    static $ensured = false;

    if ($ensured || !Database::isAvailable()) {
        return;
    }

    Database::execute(
        "CREATE TABLE IF NOT EXISTS coach_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            coach_id INT NOT NULL,
            student_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_coach_student (coach_id, student_id),
            KEY idx_coach_students_coach (coach_id),
            KEY idx_coach_students_student (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ensured = true;
}

function atlasEnsureFavoritesSchema(): void {
    static $ensured = false;

    if ($ensured || !Database::isAvailable()) {
        return;
    }

    Database::execute(
        "CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recruiter_id INT NOT NULL,
            student_id INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_fav (recruiter_id, student_id),
            KEY idx_favorites_recruiter (recruiter_id),
            KEY idx_favorites_student (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ensured = true;
}

function atlasEnsureNotificationsSchema(): void {
    static $ensured = false;

    if ($ensured || !Database::isAvailable()) {
        return;
    }

    Database::execute(
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(40),
            message TEXT,
            is_read TINYINT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notifications_user (user_id, is_read, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $ensured = true;
}

function atlasDemoFavoritesFile(): string {
    $directory = atlasEnsurePrivateDataDirectory();
    return rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'demo-favorites.json';
}

function atlasReadDemoFavoritesStore(): array {
    if (!atlasDemoModeEnabled()) {
        return [];
    }

    $file = atlasDemoFavoritesFile();

    if (!is_file($file)) {
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);

    if (!is_array($decoded)) {
        return [];
    }

    $favorites = [];

    foreach ($decoded as $favorite) {
        if (!is_array($favorite)) {
            continue;
        }

        $recruiterId = (int) ($favorite['recruiter_id'] ?? 0);
        $studentId = (int) ($favorite['student_id'] ?? 0);

        if ($recruiterId <= 0 || $studentId <= 0) {
            continue;
        }

        $favorites[] = [
            'recruiter_id' => $recruiterId,
            'student_id' => $studentId,
        ];
    }

    return $favorites;
}

function atlasWriteDemoFavoritesStore(array $favorites): void {
    $file = atlasDemoFavoritesFile();
    file_put_contents($file, json_encode(array_values($favorites), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function atlasGetUnreadNotificationCount(int $userId): int {
    if ($userId <= 0 || !Database::isAvailable()) {
        return 0;
    }

    atlasEnsureNotificationsSchema();

    $row = Database::fetchOne(
        "SELECT COUNT(*) AS total
         FROM notifications
         WHERE user_id = ? AND is_read = 0",
        [$userId]
    );

    return (int) ($row['total'] ?? 0);
}

function atlasGetFavoriteStudentIdsForRecruiter(int $recruiterId): array {
    if ($recruiterId <= 0) {
        return [];
    }

    if (Database::isAvailable()) {
        atlasEnsureFavoritesSchema();
        $rows = Database::fetchAll(
            "SELECT student_id
             FROM favorites
             WHERE recruiter_id = ?
             ORDER BY created_at DESC, id DESC",
            [$recruiterId]
        );

        return array_values(array_unique(array_filter(array_map(
            static fn(array $row): int => (int) ($row['student_id'] ?? 0),
            $rows
        ))));
    }

    return array_values(array_unique(array_filter(array_map(
        static fn(array $favorite): int => (int) ($favorite['student_id'] ?? 0),
        array_filter(atlasReadDemoFavoritesStore(), static fn(array $favorite): bool => (int) ($favorite['recruiter_id'] ?? 0) === $recruiterId)
    ))));
}

function atlasGetFavoriteStudentsForRecruiter(int $recruiterId): array {
    $favoriteStudentIds = atlasGetFavoriteStudentIdsForRecruiter($recruiterId);

    if ($favoriteStudentIds === []) {
        return [];
    }

    $visibleStudents = [];

    foreach (atlasGetRecruiterStudents() as $student) {
        $visibleStudents[(int) ($student['id'] ?? 0)] = $student;
    }

    $favorites = [];

    foreach ($favoriteStudentIds as $studentId) {
        if (isset($visibleStudents[$studentId])) {
            $favorites[] = $visibleStudents[$studentId];
        }
    }

    return $favorites;
}

function atlasSetRecruiterFavorite(int $recruiterId, int $studentId, bool $isFavorite): array {
    if ($recruiterId <= 0 || $studentId <= 0) {
        return ['success' => false, 'message' => 'Selection invalide.'];
    }

    if (!atlasCanAccessStudent(['id' => $recruiterId, 'role' => ROLE_RECRUITER], $studentId)) {
        return ['success' => false, 'message' => 'Ce talent n est pas disponible pour ce recruteur.'];
    }

    if (Database::isAvailable()) {
        atlasEnsureFavoritesSchema();

        $existing = Database::fetchOne(
            "SELECT id FROM favorites WHERE recruiter_id = ? AND student_id = ?",
            [$recruiterId, $studentId]
        );

        if ($isFavorite && !$existing) {
            Database::insert('favorites', [
                'recruiter_id' => $recruiterId,
                'student_id' => $studentId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if (!$isFavorite && $existing) {
            Database::query(
                "DELETE FROM favorites WHERE recruiter_id = ? AND student_id = ?",
                [$recruiterId, $studentId]
            );
        }

        return [
            'success' => true,
            'favorite' => $isFavorite,
            'message' => $isFavorite ? 'Favori ajoute.' : 'Favori retire.',
        ];
    }

    if (!atlasDemoModeEnabled()) {
        return ['success' => false, 'message' => 'Favoris indisponibles tant que la base MySQL n est pas configuree.'];
    }

    $favorites = atlasReadDemoFavoritesStore();
    $nextFavorites = array_values(array_filter($favorites, static function (array $favorite) use ($recruiterId, $studentId): bool {
        return (int) ($favorite['recruiter_id'] ?? 0) !== $recruiterId
            || (int) ($favorite['student_id'] ?? 0) !== $studentId;
    }));

    if ($isFavorite) {
        $nextFavorites[] = [
            'recruiter_id' => $recruiterId,
            'student_id' => $studentId,
        ];
    }

    atlasWriteDemoFavoritesStore($nextFavorites);

    return [
        'success' => true,
        'favorite' => $isFavorite,
        'message' => $isFavorite ? 'Favori ajoute.' : 'Favori retire.',
    ];
}

function atlasDemoCoachAssignmentsFile(): string {
    $directory = atlasEnsurePrivateDataDirectory();
    return rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'demo-coach-assignments.json';
}

function atlasReadDemoCoachAssignmentsStore(): array {
    $file = atlasDemoCoachAssignmentsFile();

    if (!is_file($file)) {
        $seed = [
            ['coach_id' => 3, 'student_id' => 1],
            ['coach_id' => 3, 'student_id' => 2],
            ['coach_id' => 3, 'student_id' => 5],
        ];
        file_put_contents($file, json_encode($seed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
        return $seed;
    }

    $raw = file_get_contents($file);
    $decoded = json_decode((string) $raw, true);

    if (!is_array($decoded)) {
        return [];
    }

    return array_values(array_filter(array_map(static function ($row): ?array {
        if (!is_array($row)) {
            return null;
        }

        $coachId = (int) ($row['coach_id'] ?? 0);
        $studentId = (int) ($row['student_id'] ?? 0);

        if ($coachId <= 0 || $studentId <= 0) {
            return null;
        }

        return [
            'coach_id' => $coachId,
            'student_id' => $studentId,
        ];
    }, $decoded)));
}

function atlasWriteDemoCoachAssignmentsStore(array $assignments): void {
    $file = atlasDemoCoachAssignmentsFile();
    file_put_contents($file, json_encode(array_values($assignments), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function atlasGetCoachAssignments(): array {
    if (Database::isAvailable()) {
        atlasEnsureCoachAssignmentsSchema();
        return array_map(static function (array $row): array {
            return [
                'coach_id' => (int) ($row['coach_id'] ?? 0),
                'student_id' => (int) ($row['student_id'] ?? 0),
            ];
        }, Database::fetchAll("SELECT coach_id, student_id FROM coach_students"));
    }

    return atlasReadDemoCoachAssignmentsStore();
}

function atlasAssignStudentToCoach(int $coachId, int $studentId): array {
    if ($coachId <= 0 || $studentId <= 0) {
        return ['success' => false, 'message' => 'Selection invalide.'];
    }

    $student = atlasGetStudentById($studentId);

    if (!$student) {
        return ['success' => false, 'message' => 'Athlete introuvable.'];
    }

    if (Database::isAvailable()) {
        atlasEnsureCoachAssignmentsSchema();

        $existing = Database::fetchOne(
            "SELECT id FROM coach_students WHERE coach_id = ? AND student_id = ?",
            [$coachId, $studentId]
        );

        if ($existing) {
            return ['success' => false, 'message' => 'Cet athlete est deja assigne a ce coach.'];
        }

        Database::insert('coach_students', [
            'coach_id' => $coachId,
            'student_id' => $studentId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'message' => 'Athlete ajoute au suivi avec succes.'];
    }

    $assignments = atlasReadDemoCoachAssignmentsStore();

    foreach ($assignments as $assignment) {
        if ((int) ($assignment['coach_id'] ?? 0) === $coachId && (int) ($assignment['student_id'] ?? 0) === $studentId) {
            return ['success' => false, 'message' => 'Cet athlete est deja assigne a ce coach.'];
        }
    }

    $assignments[] = [
        'coach_id' => $coachId,
        'student_id' => $studentId,
    ];

    atlasWriteDemoCoachAssignmentsStore($assignments);

    return ['success' => true, 'message' => 'Athlete ajoute au suivi avec succes.'];
}

function atlasGetUserById(int $userId): ?array {
    if ($userId <= 0) {
        return null;
    }

    foreach (atlasGetPlatformUsers() as $user) {
        if ((int) ($user['id'] ?? 0) === $userId) {
            return $user;
        }
    }

    return null;
}

function atlasGetRelevantStudentIdsForUser(array $user): array {
    $role = atlasCanonicalRole((string) ($user['role'] ?? ''));
    $studentIds = [];

    switch ($role) {
        case ROLE_TEACHER:
            foreach (atlasGetTeacherStudents((int) ($user['id'] ?? 0)) as $student) {
                $studentIds[] = (int) ($student['id'] ?? 0);
            }
            break;

        case ROLE_STUDENT:
            if (($linkedStudent = atlasGetLinkedStudentForUser($user))) {
                $studentIds[] = (int) ($linkedStudent['id'] ?? 0);
            }
            break;

        case ROLE_COACH:
            foreach (atlasGetCoachStudents((int) ($user['id'] ?? 0)) as $student) {
                $studentIds[] = (int) ($student['id'] ?? 0);
            }
            break;

        case ROLE_MANAGER:
        case ROLE_RECRUITER:
            foreach (atlasGetRecruiterStudents() as $student) {
                $studentIds[] = (int) ($student['id'] ?? 0);
            }
            break;
    }

    $studentIds = array_values(array_unique(array_filter($studentIds)));
    sort($studentIds);

    return $studentIds;
}

function atlasGetDemoChatMessages(): array {
    if (!atlasDemoModeEnabled()) {
        return [];
    }

    return [
        [
            'id' => 1,
            'sender_id' => 2,
            'recipient_id' => 1,
            'student_id' => 1,
            'body' => 'Bonjour professeur, le profil de Youssef nous interesse pour un test sprint.',
            'is_read' => 1,
            'created_at' => '2026-04-12 09:30:00',
        ],
        [
            'id' => 2,
            'sender_id' => 1,
            'recipient_id' => 2,
            'student_id' => 1,
            'body' => 'Parfait, je peux partager une nouvelle video et ses derniers temps de passage.',
            'is_read' => 1,
            'created_at' => '2026-04-12 10:05:00',
        ],
        [
            'id' => 3,
            'sender_id' => 5,
            'recipient_id' => 1,
            'student_id' => 2,
            'body' => 'Fatima Zahra a un profil tres propre. Pouvons-nous organiser une evaluation club ?',
            'is_read' => 1,
            'created_at' => '2026-04-13 11:10:00',
        ],
        [
            'id' => 4,
            'sender_id' => 1,
            'recipient_id' => 5,
            'student_id' => 2,
            'body' => 'Oui, je vous envoie ses disponibilites et son dernier resume IA.',
            'is_read' => 1,
            'created_at' => '2026-04-13 11:26:00',
        ],
        [
            'id' => 5,
            'sender_id' => 3,
            'recipient_id' => 6,
            'student_id' => 1,
            'body' => 'On garde la seance de travail vitesse jeudi a 15h.',
            'is_read' => 1,
            'created_at' => '2026-04-13 17:00:00',
        ],
    ];
}

function atlasDemoChatFile(): string {
    $directory = atlasEnsurePrivateDataDirectory();
    return rtrim($directory, '\\/') . DIRECTORY_SEPARATOR . 'demo-chat.json';
}

function atlasReadDemoChatStore(): array {
    $file = atlasDemoChatFile();

    if (!is_file($file)) {
        $seed = atlasGetDemoChatMessages();
        file_put_contents($file, json_encode($seed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
        return $seed;
    }

    $raw = file_get_contents($file);
    $decoded = json_decode((string) $raw, true);

    if (!is_array($decoded)) {
        $decoded = atlasGetDemoChatMessages();
        file_put_contents($file, json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
    }

    return array_values(array_filter(array_map(static function ($message): ?array {
        if (!is_array($message)) {
            return null;
        }

        return [
            'id' => (int) ($message['id'] ?? 0),
            'sender_id' => (int) ($message['sender_id'] ?? 0),
            'recipient_id' => (int) ($message['recipient_id'] ?? 0),
            'student_id' => isset($message['student_id']) ? (int) $message['student_id'] : null,
            'body' => trim((string) ($message['body'] ?? '')),
            'is_read' => !empty($message['is_read']) ? 1 : 0,
            'created_at' => (string) ($message['created_at'] ?? date('Y-m-d H:i:s')),
        ];
    }, $decoded)));
}

function atlasWriteDemoChatStore(array $messages): void {
    $file = atlasDemoChatFile();
    file_put_contents($file, json_encode(array_values($messages), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function atlasGetChatMessagesForUserId(int $userId): array {
    if ($userId <= 0) {
        return [];
    }

    if (Database::isAvailable()) {
        atlasEnsureChatSchema();
        return array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'sender_id' => (int) ($row['sender_id'] ?? 0),
                'recipient_id' => (int) ($row['recipient_id'] ?? 0),
                'student_id' => isset($row['student_id']) ? (int) $row['student_id'] : null,
                'body' => trim((string) ($row['body'] ?? '')),
                'is_read' => !empty($row['is_read']) ? 1 : 0,
                'created_at' => (string) ($row['created_at'] ?? date('Y-m-d H:i:s')),
            ];
        }, Database::fetchAll(
            "SELECT id, sender_id, recipient_id, student_id, body, is_read, created_at
             FROM messages
             WHERE sender_id = ? OR recipient_id = ?
             ORDER BY created_at ASC, id ASC",
            [$userId, $userId]
        ));
    }

    if (!atlasDemoModeEnabled()) {
        return [];
    }

    return array_values(array_filter(atlasReadDemoChatStore(), static function (array $message) use ($userId): bool {
        return (int) ($message['sender_id'] ?? 0) === $userId || (int) ($message['recipient_id'] ?? 0) === $userId;
    }));
}

function atlasSharedStudentIdsBetweenUsers(array $leftUser, array $rightUser): array {
    $leftIds = atlasGetRelevantStudentIdsForUser($leftUser);
    $rightIds = atlasGetRelevantStudentIdsForUser($rightUser);
    $sharedIds = array_values(array_intersect($leftIds, $rightIds));
    sort($sharedIds);

    return $sharedIds;
}

function atlasGetSmartChatContactIdsForUser(array $user): array {
    $currentUserId = (int) ($user['id'] ?? 0);

    if ($currentUserId <= 0) {
        return [];
    }

    $role = atlasCanonicalRole((string) ($user['role'] ?? ''));
    $students = atlasGetAllStudents();
    $studentsById = [];
    $studentAccountUserIds = [];
    $coachIdsByStudent = [];
    $scoutingUserIds = [];
    $contactIds = [];

    foreach ($students as $student) {
        $studentsById[(int) ($student['id'] ?? 0)] = $student;
    }

    foreach (atlasGetPlatformUsers() as $platformUser) {
        $platformUserId = (int) ($platformUser['id'] ?? 0);

        if ($platformUserId <= 0 || $platformUserId === $currentUserId) {
            continue;
        }

        if (!empty($platformUser['student_id'])) {
            $studentAccountUserIds[(int) $platformUser['student_id']][] = $platformUserId;
        }

        if (in_array(atlasCanonicalRole((string) ($platformUser['role'] ?? '')), [ROLE_MANAGER, ROLE_RECRUITER], true)) {
            $scoutingUserIds[] = $platformUserId;
        }
    }

    foreach (atlasGetCoachAssignments() as $assignment) {
        $studentId = (int) ($assignment['student_id'] ?? 0);
        $coachId = (int) ($assignment['coach_id'] ?? 0);

        if ($studentId > 0 && $coachId > 0) {
            $coachIdsByStudent[$studentId][] = $coachId;
        }
    }

    $addContact = static function (int $contactId) use (&$contactIds, $currentUserId): void {
        if ($contactId > 0 && $contactId !== $currentUserId) {
            $contactIds[$contactId] = true;
        }
    };

    if (in_array($role, [ROLE_MANAGER, ROLE_RECRUITER], true)) {
        foreach (atlasGetRecruiterStudents() as $student) {
            $studentId = (int) ($student['id'] ?? 0);
            $addContact((int) ($student['teacher_id'] ?? 0));

            foreach ($coachIdsByStudent[$studentId] ?? [] as $coachId) {
                $addContact((int) $coachId);
            }

            if ((int) ($student['score'] ?? 0) >= 85) {
                foreach ($studentAccountUserIds[$studentId] ?? [] as $studentUserId) {
                    $addContact((int) $studentUserId);
                }
            }
        }

        foreach ($scoutingUserIds as $scoutingUserId) {
            $addContact((int) $scoutingUserId);
        }
    } elseif ($role === ROLE_TEACHER) {
        $hasRecruitableProfile = false;

        foreach (atlasGetTeacherStudents($currentUserId) as $student) {
            $studentId = (int) ($student['id'] ?? 0);
            $hasRecruitableProfile = $hasRecruitableProfile || (int) ($student['score'] ?? 0) > 0;

            foreach ($studentAccountUserIds[$studentId] ?? [] as $studentUserId) {
                $addContact((int) $studentUserId);
            }

            foreach ($coachIdsByStudent[$studentId] ?? [] as $coachId) {
                $addContact((int) $coachId);
            }
        }

        if ($hasRecruitableProfile) {
            foreach ($scoutingUserIds as $scoutingUserId) {
                $addContact((int) $scoutingUserId);
            }
        }
    } elseif ($role === ROLE_COACH) {
        $hasRecruitableProfile = false;

        foreach (atlasGetCoachStudents($currentUserId) as $student) {
            $studentId = (int) ($student['id'] ?? 0);
            $hasRecruitableProfile = $hasRecruitableProfile || (int) ($student['score'] ?? 0) >= 80;
            $addContact((int) ($student['teacher_id'] ?? 0));

            foreach ($studentAccountUserIds[$studentId] ?? [] as $studentUserId) {
                $addContact((int) $studentUserId);
            }
        }

        if ($hasRecruitableProfile) {
            foreach ($scoutingUserIds as $scoutingUserId) {
                $addContact((int) $scoutingUserId);
            }
        }
    } elseif ($role === ROLE_STUDENT && ($linkedStudent = atlasGetLinkedStudentForUser($user))) {
        $studentId = (int) ($linkedStudent['id'] ?? 0);
        $addContact((int) ($linkedStudent['teacher_id'] ?? 0));

        foreach ($coachIdsByStudent[$studentId] ?? [] as $coachId) {
            $addContact((int) $coachId);
        }

        if ((int) ($linkedStudent['score'] ?? 0) >= 80) {
            foreach ($scoutingUserIds as $scoutingUserId) {
                $addContact((int) $scoutingUserId);
            }
        }
    }

    foreach (atlasGetChatMessagesForUserId($currentUserId) as $message) {
        $otherId = (int) ($message['sender_id'] ?? 0) === $currentUserId
            ? (int) ($message['recipient_id'] ?? 0)
            : (int) ($message['sender_id'] ?? 0);
        $addContact($otherId);
    }

    return array_map('intval', array_keys($contactIds));
}

function atlasCanUsersChat(array $sender, array $recipient): bool {
    $senderId = (int) ($sender['id'] ?? 0);
    $recipientId = (int) ($recipient['id'] ?? 0);

    if ($senderId <= 0 || $recipientId <= 0 || $senderId === $recipientId) {
        return false;
    }

    if (in_array($recipientId, atlasGetSmartChatContactIdsForUser($sender), true)) {
        return true;
    }

    foreach (atlasGetChatMessagesForUserId($senderId) as $message) {
        $otherId = (int) ($message['sender_id'] ?? 0) === $senderId
            ? (int) ($message['recipient_id'] ?? 0)
            : (int) ($message['sender_id'] ?? 0);

        if ($otherId === $recipientId) {
            return true;
        }
    }

    return false;
}

function atlasChatExcerpt(string $message, int $limit = 84): string {
    $message = trim($message);

    if ($message === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($message) > $limit ? mb_substr($message, 0, $limit - 1) . '…' : $message;
    }

    return strlen($message) > $limit ? substr($message, 0, $limit - 1) . '…' : $message;
}

function atlasChatDayLabel(string $datetime): string {
    $timestamp = strtotime($datetime);
    $today = strtotime(date('Y-m-d'));
    $messageDay = strtotime(date('Y-m-d', $timestamp));

    if ($messageDay === $today) {
        return 'Aujourd hui';
    }

    if ($messageDay === strtotime('-1 day', $today)) {
        return 'Hier';
    }

    return formatLongDate(date('Y-m-d', $timestamp), true);
}

function atlasChatRoleBucket(string $role): string {
    return atlasCanonicalRole($role) === ROLE_STUDENT ? 'talent' : 'staff';
}

function atlasChatSuggestionsForPair(array $currentUser, array $contactUser, array $relatedStudents): array {
    $currentRole = atlasCanonicalRole((string) ($currentUser['role'] ?? ''));
    $contactRole = atlasCanonicalRole((string) ($contactUser['role'] ?? ''));
    $firstStudentName = $relatedStudents[0]['name'] ?? 'le talent';

    $suggestions = match ($currentRole) {
        ROLE_MANAGER, ROLE_RECRUITER => [
            "Peut-on organiser une evaluation pour {$firstStudentName} ?",
            "Avez-vous une mise a jour recente sur {$firstStudentName} ?",
            "Quel est le meilleur prochain pas pour ce profil ?",
        ],
        ROLE_TEACHER => [
            "Je partage une mise a jour sur {$firstStudentName}.",
            "Souhaitez-vous une nouvelle video d evaluation ?",
            "Nous pouvons caler un echange sur le profil cette semaine.",
        ],
        ROLE_COACH => [
            "Voici le point de suivi le plus recent sur {$firstStudentName}.",
            "Je propose une seance test la semaine prochaine.",
            "Le travail prioritaire reste la progression physique.",
        ],
        ROLE_STUDENT => [
            "Je suis disponible pour un echange cette semaine.",
            "Je peux partager mes prochaines disponibilites.",
            "Pouvez-vous me confirmer la prochaine etape ?",
        ],
        default => [
            'Pouvons-nous faire un point rapide ?',
            'J ai une mise a jour utile a partager.',
            'Quelle est la prochaine etape ?',
        ],
    };

    if ($contactRole === ROLE_STUDENT) {
        $suggestions[0] = "Bonjour, faisons un point sur {$firstStudentName}.";
    }

    return array_values(array_slice(array_unique(array_filter(array_map('trim', $suggestions))), 0, 3));
}

function atlasMarkConversationRead(int $userId, int $contactId): void {
    if ($userId <= 0 || $contactId <= 0) {
        return;
    }

    if (Database::isAvailable()) {
        atlasEnsureChatSchema();
        Database::query(
            "UPDATE messages
             SET is_read = 1
             WHERE sender_id = ? AND recipient_id = ? AND is_read = 0",
            [$contactId, $userId]
        );
        return;
    }

    if (!atlasDemoModeEnabled()) {
        return;
    }

    $messages = atlasReadDemoChatStore();
    $updated = false;

    foreach ($messages as $index => $message) {
        if ((int) ($message['sender_id'] ?? 0) === $contactId
            && (int) ($message['recipient_id'] ?? 0) === $userId
            && empty($message['is_read'])) {
            $messages[$index]['is_read'] = 1;
            $updated = true;
        }
    }

    if ($updated) {
        atlasWriteDemoChatStore($messages);
    }
}

function atlasGetChatDataForUser(array $user): array {
    $currentUserId = (int) ($user['id'] ?? 0);
    $usersById = [];
    $studentsById = [];
    $messagesByContact = [];
    $contactIds = atlasGetSmartChatContactIdsForUser($user);

    foreach (atlasGetPlatformUsers() as $platformUser) {
        $usersById[(int) ($platformUser['id'] ?? 0)] = $platformUser;
    }

    foreach (atlasGetAllStudents() as $student) {
        $studentsById[(int) ($student['id'] ?? 0)] = $student;
    }

    foreach (atlasGetChatMessagesForUserId($currentUserId) as $message) {
        $otherId = (int) ($message['sender_id'] ?? 0) === $currentUserId
            ? (int) ($message['recipient_id'] ?? 0)
            : (int) ($message['sender_id'] ?? 0);

        if ($otherId > 0) {
            $messagesByContact[$otherId][] = $message;

            if (!in_array($otherId, $contactIds, true)) {
                $contactIds[] = $otherId;
            }
        }
    }

    $contacts = [];

    foreach (array_values(array_unique(array_filter($contactIds))) as $contactId) {
        $contactUser = $usersById[$contactId] ?? null;

        if (!$contactUser) {
            continue;
        }

        $sharedStudentIds = atlasSharedStudentIdsBetweenUsers($user, $contactUser);

        foreach ($messagesByContact[$contactId] ?? [] as $threadMessage) {
            $messageStudentId = isset($threadMessage['student_id']) ? (int) $threadMessage['student_id'] : 0;

            if ($messageStudentId > 0 && !in_array($messageStudentId, $sharedStudentIds, true)) {
                $sharedStudentIds[] = $messageStudentId;
            }
        }

        sort($sharedStudentIds);

        $relatedStudents = array_values(array_filter(array_map(static function (int $studentId) use ($studentsById): ?array {
            $student = $studentsById[$studentId] ?? null;

            if (!$student) {
                return null;
            }

            return [
                'id' => (int) ($student['id'] ?? 0),
                'name' => (string) ($student['name'] ?? 'Talent'),
            ];
        }, $sharedStudentIds)));

        $thread = array_map(static function (array $message) use ($currentUserId, $studentsById): array {
            $studentId = isset($message['student_id']) ? (int) $message['student_id'] : null;
            $student = $studentId ? ($studentsById[$studentId] ?? null) : null;
            $sentAt = (string) ($message['created_at'] ?? date('Y-m-d H:i:s'));

            return [
                'id' => (int) ($message['id'] ?? 0),
                'sender_id' => (int) ($message['sender_id'] ?? 0),
                'recipient_id' => (int) ($message['recipient_id'] ?? 0),
                'body' => (string) ($message['body'] ?? ''),
                'is_mine' => (int) ($message['sender_id'] ?? 0) === $currentUserId,
                'is_read' => !empty($message['is_read']),
                'student_id' => $studentId ?: null,
                'student_name' => $student['name'] ?? null,
                'sent_at' => $sentAt,
                'time_label' => timeAgo($sentAt),
                'time_exact' => date('H:i', strtotime($sentAt)),
                'day_label' => atlasChatDayLabel($sentAt),
            ];
        }, $messagesByContact[$contactId] ?? []);

        $lastMessage = $thread !== [] ? $thread[count($thread) - 1] : null;
        $unreadCount = count(array_filter($thread, static function (array $message): bool {
            return !$message['is_mine'] && empty($message['is_read']);
        }));
        $contextNames = array_map(static fn(array $entry): string => (string) ($entry['name'] ?? ''), array_slice($relatedStudents, 0, 3));
        $contextLabel = $contextNames !== []
            ? 'Autour de ' . implode(', ', $contextNames)
            : atlasRoleLabel((string) ($contactUser['role'] ?? ''));
        $role = atlasCanonicalRole((string) ($contactUser['role'] ?? ''));

        $contacts[] = [
            'id' => (int) ($contactUser['id'] ?? 0),
            'name' => (string) ($contactUser['name'] ?? 'Utilisateur'),
            'role' => $role,
            'role_label' => atlasRoleLabel((string) ($contactUser['role'] ?? '')),
            'role_bucket' => atlasChatRoleBucket($role),
            'subtitle' => userSubtitle($contactUser),
            'avatar' => initials((string) ($contactUser['name'] ?? 'U')),
            'avatar_class' => atlasRoleAvatarClass((string) ($contactUser['role'] ?? '')),
            'context_label' => $contextLabel,
            'related_students' => $relatedStudents,
            'related_student_count' => count($relatedStudents),
            'latest_message' => $lastMessage ? atlasChatExcerpt((string) ($lastMessage['body'] ?? '')) : 'Aucun message pour le moment.',
            'latest_time_label' => $lastMessage['time_label'] ?? '',
            'latest_sent_at' => $lastMessage['sent_at'] ?? '',
            'unread_count' => $unreadCount,
            'has_messages' => $thread !== [],
            'suggested_prompts' => atlasChatSuggestionsForPair($user, $contactUser, $relatedStudents),
            'messages' => $thread,
        ];
    }

    usort($contacts, static function (array $left, array $right): int {
        $leftStamp = (string) ($left['latest_sent_at'] ?? '');
        $rightStamp = (string) ($right['latest_sent_at'] ?? '');

        if ($leftStamp === $rightStamp) {
            return strcmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        }

        return strcmp($rightStamp, $leftStamp);
    });

    $unreadTotal = array_sum(array_map(static fn(array $contact): int => (int) ($contact['unread_count'] ?? 0), $contacts));
    $unreadContacts = count(array_filter($contacts, static fn(array $contact): bool => (int) ($contact['unread_count'] ?? 0) > 0));

    return [
        'contacts' => $contacts,
        'active_contact_id' => isset($contacts[0]['id']) ? (int) $contacts[0]['id'] : null,
        'contact_count' => count($contacts),
        'unread_total' => $unreadTotal,
        'unread_contact_count' => $unreadContacts,
    ];
}

function atlasStoreChatMessage(array $payload): array {
    $stored = [
        'sender_id' => (int) ($payload['sender_id'] ?? 0),
        'recipient_id' => (int) ($payload['recipient_id'] ?? 0),
        'student_id' => isset($payload['student_id']) && (int) $payload['student_id'] > 0 ? (int) $payload['student_id'] : null,
        'body' => trim((string) ($payload['body'] ?? '')),
        'is_read' => 0,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    if (Database::isAvailable()) {
        atlasEnsureChatSchema();
        $stored['id'] = Database::insert('messages', $stored);
        return $stored;
    }

    if (!atlasDemoModeEnabled()) {
        throw new RuntimeException('Messagerie indisponible tant que la base MySQL n est pas configuree.');
    }

    $messages = atlasReadDemoChatStore();
    $stored['id'] = $messages !== [] ? (max(array_column($messages, 'id')) + 1) : 1;
    $messages[] = $stored;
    atlasWriteDemoChatStore($messages);

    return $stored;
}

function atlasSendChatMessage(array $sender, int $recipientId, string $body, ?int $studentId = null): array {
    $senderId = (int) ($sender['id'] ?? 0);
    $recipient = atlasGetUserById($recipientId);
    $body = trim(preg_replace('/\s+/', ' ', $body));

    if ($senderId <= 0 || !$recipient) {
        throw new RuntimeException('Destinataire introuvable.');
    }

    if ($body === '') {
        throw new RuntimeException('Le message ne peut pas etre vide.');
    }

    if (!atlasCanUsersChat($sender, $recipient)) {
        throw new RuntimeException('Cette conversation n est pas autorisee pour ce profil.');
    }

    $sharedStudentIds = atlasSharedStudentIdsBetweenUsers($sender, $recipient);

    if ($studentId !== null && !in_array($studentId, $sharedStudentIds, true)) {
        $studentId = null;
    }

    if ($studentId === null && count($sharedStudentIds) === 1) {
        $studentId = (int) $sharedStudentIds[0];
    }

    return atlasStoreChatMessage([
        'sender_id' => $senderId,
        'recipient_id' => $recipientId,
        'student_id' => $studentId,
        'body' => $body,
    ]);
}
