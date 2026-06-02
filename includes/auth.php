<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';

class Auth {
    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $cookieSecure = atlas_is_https_request();

            ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', $cookieSecure ? '1' : '0');
            ini_set('session.cookie_samesite', 'Lax');
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path' => '/',
                'secure' => $cookieSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    private static function buildAvatar(string $name): string {
        return initials($name);
    }

    private static function setUserSession(array $user): void {
        self::init();
        session_regenerate_id(true);

        $canonicalRole = atlasCanonicalRole((string) ($user['role'] ?? ''));

        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['user_name'] = $user['name'] ?? '';
        $_SESSION['user_role'] = $canonicalRole;
        $_SESSION['user_role_source'] = $user['role'] ?? $canonicalRole;
        $_SESSION['user_club'] = $user['club'] ?? '';
        $_SESSION['user_ville'] = $user['ville'] ?? '';
        $_SESSION['user_avatar'] = self::buildAvatar($user['name'] ?? '');
        $_SESSION['user_student_id'] = isset($user['student_id']) ? (int) $user['student_id'] : 0;
        $_SESSION['logged_at'] = time();
    }

    private static function clearSession(): void {
        self::init();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    private static function sessionExpired(): bool {
        return !empty($_SESSION['logged_at']) && (time() - (int) $_SESSION['logged_at']) > SESSION_LIFETIME;
    }

    private static function findDatabaseUser(string $email, string $role): ?array {
        if (!Database::isAvailable()) {
            return null;
        }

        atlasEnsureRoleSchema();
        $lookupRoles = atlasRoleLookupVariants($role);

        if ($lookupRoles === []) {
            return null;
        }

        $placeholders = implode(', ', array_fill(0, count($lookupRoles), '?'));
        $user = Database::fetchOne(
            "SELECT * FROM users WHERE email = ? AND role IN ($placeholders) AND is_active = 1",
            array_merge([$email], $lookupRoles)
        );

        if ($user) {
            $user['role'] = atlasCanonicalRole((string) ($user['role'] ?? ''));
        }

        return $user;
    }

    public static function login(string $email, string $password, string $role): array {
        $email = trim($email);
        $role = atlasCanonicalRole(trim($role));
        $user = self::findDatabaseUser($email, $role);

        if (!$user && atlasDemoModeEnabled()) {
            $user = findDemoUser($email, $role);
        }

        if (!$user || !password_verify($password, (string) ($user['password'] ?? ''))) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        self::setUserSession($user);

        return ['success' => true, 'role' => atlasCanonicalRole((string) ($user['role'] ?? ''))];
    }

    public static function loginDemoRole(string $role): array {
        self::init();

        if (!atlasDemoModeEnabled()) {
            return ['success' => false, 'message' => 'Acces demo indisponible.'];
        }

        $role = atlasCanonicalRole(trim($role));
        $demoUsers = array_values(array_filter(getDemoUsers(), static function (array $user) use ($role): bool {
            return atlasCanonicalRole((string) ($user['role'] ?? '')) === $role;
        }));
        $user = $demoUsers[0] ?? null;

        if (!$user) {
            return ['success' => false, 'message' => 'Compte demo introuvable pour ce role.'];
        }

        self::setUserSession($user);

        if (atlasOwnerDemoAccessActive()) {
            $_SESSION['owner_demo_access'] = 1;
        }

        return ['success' => true, 'role' => atlasCanonicalRole((string) ($user['role'] ?? ''))];
    }

    public static function logout(?string $csrfToken = null): void {
        self::init();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit('Method not allowed');
        }

        if ($csrfToken === null || !self::verifyCsrf($csrfToken)) {
            http_response_code(419);
            exit('Jeton CSRF invalide.');
        }

        atlasOwnerDemoAccessRevoke();
        self::clearSession();
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }

    public static function check(string $requiredRole = ''): void {
        self::init();

        if (self::sessionExpired()) {
            self::clearSession();
            header('Location: ' . APP_URL . '/pages/auth/login.php?error=' . urlencode('Session expiree.'));
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/pages/auth/login.php');
            exit;
        }

        if ($requiredRole && atlasCanonicalRole((string) ($_SESSION['user_role'] ?? '')) !== atlasCanonicalRole($requiredRole)) {
            header('Location: ' . APP_URL . '/index.php?error=unauthorized');
            exit;
        }
    }

    public static function isLoggedIn(): bool {
        self::init();

        if (self::sessionExpired()) {
            self::clearSession();
            return false;
        }

        return !empty($_SESSION['user_id']);
    }

    public static function user(): array {
        return [
            'id' => (int) ($_SESSION['user_id'] ?? 0),
            'name' => $_SESSION['user_name'] ?? '',
            'role' => atlasCanonicalRole((string) ($_SESSION['user_role'] ?? '')),
            'club' => $_SESSION['user_club'] ?? '',
            'ville' => $_SESSION['user_ville'] ?? '',
            'avatar' => $_SESSION['user_avatar'] ?? 'U',
            'student_id' => (int) ($_SESSION['user_student_id'] ?? 0),
        ];
    }

    public static function register(array $data): array {
        if (!Database::isAvailable()) {
            return [
                'success' => false,
                'message' => 'Inscription indisponible tant que la base MySQL n est pas configuree.',
            ];
        }

        atlasEnsureRoleSchema();
        $role = atlasCanonicalRole((string) ($data['role'] ?? ''));
        $allowedRoles = atlasAllowedPublicRegistrationRoles();

        if (!in_array($role, $allowedRoles, true)) {
            return ['success' => false, 'message' => 'Role invalide pour l inscription publique.'];
        }

        $exists = Database::fetchOne("SELECT id FROM users WHERE email = ?", [$data['email'] ?? '']);

        if ($exists) {
            return ['success' => false, 'message' => 'Cet email est deja utilise.'];
        }

        Database::insert('users', [
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => password_hash((string) ($data['password'] ?? ''), PASSWORD_DEFAULT),
            'role' => $role,
            'club' => $data['club'] ?? null,
            'ville' => $data['ville'] ?? null,
            'student_id' => $data['student_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'is_active' => 1,
        ]);

        return ['success' => true, 'message' => 'Compte cree avec succes !'];
    }

    public static function csrfToken(): string {
        self::init();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        self::init();
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
