<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/seo.php';

function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    // Lax preserves the administrator session for Google's top-level OAuth callback.
    ini_set('session.cookie_samesite', 'Lax');
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

const PDF_MAX_SIZE = 41943040;
const VIDEO_MAX_SIZE = 104857600;
const POST_IMAGE_MAX_SIZE = 10485760;
$configuredPdfStorage = getenv('PDF_STORAGE_PATH');
define(
    'PDF_STORAGE',
    $configuredPdfStorage !== false && $configuredPdfStorage !== ''
        ? $configuredPdfStorage
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cmq-private-storage' . DIRECTORY_SEPARATOR . 'pdfs'
);

$configuredVideoStorage = getenv('VIDEO_STORAGE_PATH');
define(
    'VIDEO_STORAGE',
    $configuredVideoStorage !== false && $configuredVideoStorage !== ''
        ? $configuredVideoStorage
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cmq-private-storage' . DIRECTORY_SEPARATOR . 'videos'
);

$configuredPostImageStorage = getenv('POST_IMAGE_STORAGE_PATH');
define(
    'POST_IMAGE_STORAGE',
    $configuredPostImageStorage !== false && $configuredPostImageStorage !== ''
        ? $configuredPostImageStorage
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cmq-private-storage' . DIRECTORY_SEPARATOR . 'post-images'
);

$configuredAppSecretStorage = getenv('APP_SECRET_STORAGE_PATH');
define(
    'APP_SECRET_STORAGE',
    $configuredAppSecretStorage !== false && $configuredAppSecretStorage !== ''
        ? $configuredAppSecretStorage
        : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'cmq-private-storage' . DIRECTORY_SEPARATOR . 'app-secrets'
);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Administrative, processing and private-file endpoints must never be search
// results. Public pages explicitly opt in through the shared page header.
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (!seo_is_public_script() || in_array($currentScript, ['process-pqrsf.php', 'download.php', 'post-image.php', 'video.php', 'sitemap.php'], true)) {
    seo_send_noindex();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function is_admin(): bool
{
    app_start_session();
    if (!isset($_SESSION['admin_id'], $_SESSION['admin_version']) || !is_int($_SESSION['admin_id'])) return false;
    $query = db()->prepare('SELECT session_version FROM usuarios WHERE id = ?');
    $query->execute([$_SESSION['admin_id']]);
    $version = $query->fetchColumn();
    if ($version !== false && (int) $version === (int) $_SESSION['admin_version']) return true;
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_version']);
    return false;
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('login.php');
    }
}

function csrf_token(): string
{
    app_start_session();
    if (!isset($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['_csrf'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(400);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }
}

function flash(?string $type = null, ?string $message = null): ?array
{
    if ($type !== null && $message !== null) {
        app_start_session();
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
        return null;
    }

    // Public cacheable pages do not need a PHP session merely to check for a
    // flash message. Administration and PQRSF start one explicitly before
    // rendering when they require session-backed state.
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    $stored = $_SESSION['_flash'] ?? null;
    unset($_SESSION['_flash']);
    return is_array($stored) ? $stored : null;
}

function post_excerpt(string $content, int $length = 180): string
{
    $plain = trim(preg_replace('/\s+/', ' ', $content) ?? '');
    if (text_length($plain) <= $length) {
        return $plain;
    }
    if (preg_match_all('/./us', $plain, $characters) === false) {
        return rtrim(substr($plain, 0, $length)) . '…';
    }
    return rtrim(implode('', array_slice($characters[0], 0, $length))) . '…';
}

function text_length(string $text): int
{
    $count = preg_match_all('/./us', $text, $matches);
    return $count === false ? strlen($text) : $count;
}

function format_date(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp === false ? '' : date('d/m/Y', $timestamp);
}
