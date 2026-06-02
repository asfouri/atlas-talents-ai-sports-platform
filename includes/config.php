<?php
if (!function_exists('atlas_env')) {
    function atlas_env(string $key, ?string $default = null): ?string {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}

if (!function_exists('atlas_env_bool')) {
    function atlas_env_bool(string $key, bool $default = false): bool {
        $value = atlas_env($key);

        if ($value === null) {
            return $default;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('atlas_normalize_path')) {
    function atlas_normalize_path(string $path): string {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('atlas_is_https_request')) {
    function atlas_is_https_request(): bool {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));

        if ($https !== '' && $https !== 'off') {
            return true;
        }

        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        return $forwardedProto === 'https';
    }
}

if (!function_exists('atlas_sanitize_host')) {
    function atlas_sanitize_host(string $host): string {
        $host = trim($host);

        if ($host !== '' && preg_match('/\A[a-z0-9.-]+(?::\d{1,5})?\z/i', $host) === 1) {
            return $host;
        }

        return 'localhost';
    }
}

if (!function_exists('atlas_detect_app_path')) {
    function atlas_detect_app_path(): string {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $projectRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);

        if (!$documentRoot) {
            return '/atlas-talents';
        }

        $normalizedDocumentRoot = rtrim(atlas_normalize_path(realpath($documentRoot) ?: $documentRoot), '/');
        $normalizedProjectRoot = atlas_normalize_path($projectRoot);

        if (stripos($normalizedProjectRoot, $normalizedDocumentRoot) !== 0) {
            return '/atlas-talents';
        }

        $relativePath = trim(substr($normalizedProjectRoot, strlen($normalizedDocumentRoot)), '/');
        return $relativePath === '' ? '' : '/' . $relativePath;
    }
}

if (!function_exists('atlas_detect_app_url')) {
    function atlas_detect_app_url(): string {
        $host = atlas_sanitize_host((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
        $scheme = atlas_is_https_request() ? 'https' : 'http';

        return rtrim($scheme . '://' . $host . atlas_detect_app_path(), '/');
    }
}

if (!function_exists('atlas_send_security_headers')) {
    function atlas_send_security_headers(string $context = 'html'): void {
        static $sentFor = [];

        if (headers_sent() || isset($sentFor[$context])) {
            return;
        }

        $sentFor[$context] = true;

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: accelerometer=(), autoplay=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header('Cross-Origin-Resource-Policy: same-origin');

        if (atlas_is_https_request()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $csp = match ($context) {
            'api' => "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'",
            'media' => "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; media-src 'self'",
            default => "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data: blob:; media-src 'self' blob:; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self'",
        };

        header('Content-Security-Policy: ' . $csp);
    }
}

define('APP_NAME', 'Atlas Talents');
define('APP_VERSION', '2.0.0');
define('APP_ENV', atlas_env('APP_ENV', 'production'));
define('APP_DEBUG', atlas_env_bool('APP_DEBUG', false));
define('APP_ROOT', dirname(__DIR__));
define('APP_PATH', atlas_detect_app_path());
define('APP_URL', rtrim((string) atlas_env('APP_URL', atlas_detect_app_url()), '/'));
define('APP_ALLOW_DEMO_MODE', atlas_env_bool('APP_ALLOW_DEMO_MODE', false));
define('APP_DEMO_ACCESS_SECRET', atlas_env('APP_DEMO_ACCESS_SECRET', ''));
define('APP_ALLOWED_PUBLIC_REGISTRATION_ROLES', atlas_env('APP_ALLOWED_PUBLIC_REGISTRATION_ROLES', 'teacher'));
define('APP_STORAGE_ROOT', atlas_normalize_path((string) atlas_env('APP_STORAGE_ROOT', APP_ROOT . '/storage')));

// Database config (MySQL)
define('DB_HOST', atlas_env('DB_HOST', 'localhost'));
define('DB_NAME', atlas_env('DB_NAME', 'atlas_talents'));
define('DB_USER', atlas_env('DB_USER', 'root'));
define('DB_PASS', atlas_env('DB_PASS', ''));
define('DB_CHARSET', atlas_env('DB_CHARSET', 'utf8mb4'));

// Session
define('SESSION_LIFETIME', 3600 * 24);

// Upload
define('UPLOAD_DIR', APP_STORAGE_ROOT . '/uploads/');
define('PRIVATE_DATA_DIR', APP_STORAGE_ROOT . '/private/');
define('LEGACY_PUBLIC_UPLOAD_DIR', APP_ROOT . '/public/uploads/');
define('UPLOAD_MAX_SIZE', 500 * 1024 * 1024); // 500MB
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/quicktime', 'video/x-msvideo']);
define('AI_FRAME_LIMIT', 6);
define('AI_FRAME_IMAGE_WIDTH', 512);

// OpenAI
define('OPENAI_API_KEY', atlas_env('OPENAI_API_KEY', ''));
define('OPENAI_MODEL', atlas_env('OPENAI_MODEL', 'gpt-4.1-mini'));
define('OPENAI_API_URL', atlas_env('OPENAI_API_URL', 'https://api.openai.com/v1/responses'));
define('OPENAI_TIMEOUT', (int) atlas_env('OPENAI_TIMEOUT', '90'));
define('AI_ALLOW_DEMO_FALLBACK', atlas_env_bool('AI_ALLOW_DEMO_FALLBACK', false));

// Roles
define('ROLE_TEACHER',   'teacher');
define('ROLE_STUDENT',   'student');
define('ROLE_MANAGER',   'manager');
define('ROLE_RECRUITER', 'recruiter');
define('ROLE_COACH',     'coach');
define('ROLE_ADMIN',     'admin');

// AI Score criteria
define('CRITERIA', ['vitesse', 'coordination', 'endurance', 'force', 'souplesse']);
define('CRITERIA_LABELS', [
    'vitesse'      => 'Vitesse',
    'coordination' => 'Coordination',
    'endurance'    => 'Endurance',
    'force'        => 'Force',
    'souplesse'    => 'Souplesse',
]);
define('CRITERIA_COLORS', [
    'vitesse'      => '#FF8F00',
    'coordination' => '#1565C0',
    'endurance'    => '#2E7D32',
    'force'        => '#9C27B0',
    'souplesse'    => '#00ACC1',
]);

// Sports
define('SPORTS', [
    'athletisme'  => 'Athlétisme',
    'football'    => 'Football',
    'basketball'  => 'Basketball',
    'handball'    => 'Handball',
    'natation'    => 'Natation',
    'gymnastique' => 'Gymnastique',
    'tennis'      => 'Tennis',
    'judo'        => 'Judo',
]);

// Villes Maroc
define('VILLES', [
    'casablanca'  => 'Casablanca',
    'rabat'       => 'Rabat',
    'marrakech'   => 'Marrakech',
    'fes'         => 'Fès',
    'tanger'      => 'Tanger',
    'agadir'      => 'Agadir',
    'oujda'       => 'Oujda',
    'meknes'      => 'Meknès',
]);
