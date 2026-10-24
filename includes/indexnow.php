<?php
declare(strict_types=1);

require_once __DIR__ . '/seo.php';

const INDEXNOW_KEY_FILE = '7a9e7d560ea5da946d44edf4c1a20370.txt';
const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const INDEXNOW_RATE_LIMIT_SECONDS = 86400;

function indexnow_cooldown_path(): ?string
{
    if (!defined('APP_SECRET_STORAGE') || !is_string(APP_SECRET_STORAGE) || APP_SECRET_STORAGE === '') {
        return null;
    }
    if (!is_dir(APP_SECRET_STORAGE) && !mkdir(APP_SECRET_STORAGE, 0750, true) && !is_dir(APP_SECRET_STORAGE)) {
        error_log('IndexNow: no fue posible preparar el almacenamiento privado del límite de frecuencia.');
        return null;
    }
    return APP_SECRET_STORAGE . DIRECTORY_SEPARATOR . 'indexnow-rate-limit.until';
}

function indexnow_is_rate_limited(): bool
{
    $path = indexnow_cooldown_path();
    if ($path === null || !is_readable($path)) {
        return false;
    }
    return (int) trim((string) file_get_contents($path)) > time();
}

function indexnow_record_rate_limit(): void
{
    $path = indexnow_cooldown_path();
    if ($path === null) {
        return;
    }
    if (file_put_contents($path, (string) (time() + INDEXNOW_RATE_LIMIT_SECONDS) . "\n", LOCK_EX) === false) {
        error_log('IndexNow: no fue posible guardar el límite de frecuencia.');
    }
}

function indexnow_key(): ?string
{
    static $key = false;
    if ($key !== false) {
        return $key;
    }

    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . INDEXNOW_KEY_FILE;
    $value = is_readable($path) ? trim((string) file_get_contents($path)) : '';
    $key = preg_match('/\A[A-Za-z0-9-]{8,128}\z/', $value) === 1 ? $value : null;
    return $key;
}

/** @param list<string> $urls @return list<string> */
function indexnow_valid_urls(array $urls): array
{
    $siteUrl = seo_site_url();
    if ($siteUrl === null) {
        return [];
    }

    $site = parse_url($siteUrl);
    $scheme = strtolower((string) ($site['scheme'] ?? ''));
    $host = strtolower((string) ($site['host'] ?? ''));
    $port = $site['port'] ?? null;
    if ($scheme !== 'https' || $host === '') {
        return [];
    }

    $valid = [];
    foreach ($urls as $url) {
        if (!is_string($url) || $url === '') {
            continue;
        }
        $parts = parse_url($url);
        if ($parts === false
            || strtolower((string) ($parts['scheme'] ?? '')) !== $scheme
            || strtolower((string) ($parts['host'] ?? '')) !== $host
            || ($parts['port'] ?? null) !== $port
            || isset($parts['user'], $parts['pass'], $parts['fragment'])) {
            continue;
        }
        $valid[$url] = true;
        if (count($valid) >= 10000) {
            break;
        }
    }

    return array_keys($valid);
}

/** @param list<string> $urls @return array{host:string,key:string,keyLocation:string,urlList:list<string>}|null */
function indexnow_payload(array $urls): ?array
{
    $siteUrl = seo_site_url();
    $key = indexnow_key();
    $validUrls = indexnow_valid_urls($urls);
    if ($siteUrl === null || $key === null || $validUrls === []) {
        return null;
    }

    $host = (string) (parse_url($siteUrl, PHP_URL_HOST) ?? '');
    $keyLocation = seo_absolute_url(INDEXNOW_KEY_FILE);
    if ($host === '' || $keyLocation === null) {
        return null;
    }

    return [
        'host' => $host,
        'key' => $key,
        'keyLocation' => $keyLocation,
        'urlList' => $validUrls,
    ];
}

/** @param array{host:string,key:string,keyLocation:string,urlList:list<string>} $payload */
function indexnow_send_payload(array $payload): bool
{
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($json)) {
        error_log('IndexNow: no fue posible serializar la notificación.');
        return false;
    }

    if (function_exists('curl_init')) {
        $handle = curl_init(INDEXNOW_ENDPOINT);
        if ($handle === false) {
            error_log('IndexNow: no fue posible iniciar cURL.');
            return false;
        }
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if (in_array($statusCode, [200, 202], true)) {
            return true;
        }
        if ($statusCode === 429) {
            indexnow_record_rate_limit();
        }
        error_log('IndexNow: respuesta HTTP ' . $statusCode . ($error !== '' ? ' (' . $error . ')' : '') . '.');
        return false;
    }

    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        error_log('IndexNow: cURL y allow_url_fopen no están disponibles.');
        return false;
    }

    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json; charset=utf-8\r\n",
        'content' => $json,
        'timeout' => 3,
        'ignore_errors' => true,
    ]]);
    $response = @file_get_contents(INDEXNOW_ENDPOINT, false, $context);
    $headers = $http_response_header ?? [];
    $statusCode = 0;
    if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $matches) === 1) {
        $statusCode = (int) $matches[1];
    }
    if ($response !== false && in_array($statusCode, [200, 202], true)) {
        return true;
    }

    if ($statusCode === 429) {
        indexnow_record_rate_limit();
    }

    error_log('IndexNow: respuesta HTTP ' . $statusCode . ' mediante stream.');
    return false;
}

/** @param list<string> $urls */
function indexnow_notify_urls(array $urls): bool
{
    if (indexnow_is_rate_limited()) {
        error_log('IndexNow: notificación omitida por límite de frecuencia activo.');
        return false;
    }
    $payload = indexnow_payload($urls);
    if ($payload === null) {
        error_log('IndexNow: notificación omitida; falta una clave válida o URLs canónicas.');
        return false;
    }

    return indexnow_send_payload($payload);
}

/** @param list<string> $paths */
function indexnow_notify_paths(array $paths): bool
{
    $urls = [];
    foreach ($paths as $path) {
        $url = seo_absolute_url($path);
        if ($url !== null) {
            $urls[] = $url;
        }
    }
    return indexnow_notify_urls($urls);
}
