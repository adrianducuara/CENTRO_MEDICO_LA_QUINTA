<?php
declare(strict_types=1);

require_once __DIR__ . '/runtime_config.php';

/**
 * Returns the configured public origin (with an optional installation path).
 * It deliberately never infers an origin from the Host header: doing so could
 * produce poisoned canonical URLs when a proxy accepts an untrusted host.
 */
function seo_site_url(): ?string
{
    static $siteUrl = false;
    if ($siteUrl !== false) {
        return $siteUrl === '' ? null : $siteUrl;
    }

    $configured = trim(runtime_config_value('APP_URL'));
    if ($configured === '') {
        $siteUrl = '';
        return null;
    }

    $parts = parse_url($configured);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = (string) ($parts['host'] ?? '');
    $path = rtrim((string) ($parts['path'] ?? ''), '/');
    $isLocal = in_array(strtolower($host), ['localhost', '127.0.0.1'], true);
    $hasForbiddenComponent = isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment']);
    if ($host === '' || $hasForbiddenComponent || ($isLocal ? !in_array($scheme, ['http', 'https'], true) : $scheme !== 'https')) {
        throw new RuntimeException('APP_URL debe ser una URL canónica HTTPS válida, sin consulta, fragmento ni credenciales.');
    }

    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $siteUrl = $scheme . '://' . strtolower($host) . $port . $path;
    return $siteUrl;
}

function seo_absolute_url(string $path = ''): ?string
{
    $siteUrl = seo_site_url();
    if ($siteUrl === null) {
        return null;
    }
    return $siteUrl . '/' . ltrim($path, '/');
}

function seo_is_public_script(): bool
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return !str_contains($script, '/admin/');
}

function seo_send_noindex(): void
{
    header('X-Robots-Tag: noindex, nofollow, noarchive', true);
}

/** @return array<string, mixed> */
function seo_medical_clinic_schema(): array
{
    $url = seo_absolute_url();
    if ($url === null) {
        return [];
    }

    return [
        '@context' => 'https://schema.org',
        '@type' => ['MedicalClinic', 'LocalBusiness'],
        '@id' => $url . '#centro-medico-la-quinta',
        'name' => 'Centro Médico La Quinta',
        'legalName' => 'Clínica Integral Provida S.A.S.',
        'url' => $url,
        'logo' => seo_absolute_url('public/assets/images/cmq-clinic-logo-transparent.png'),
        'image' => seo_absolute_url('public/assets/images/fachada-clinica.jpeg'),
        'telephone' => '+57 316 831 2283',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'Cra. 5 #39-72',
            'addressLocality' => 'Ibagué',
            'addressRegion' => 'Tolima',
            'addressCountry' => 'CO',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => 4.436151,
            'longitude' => -75.213724,
        ],
        'openingHoursSpecification' => [
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                'opens' => '06:30',
                'closes' => '19:00',
            ],
            [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => 'Saturday',
                'opens' => '07:00',
                'closes' => '17:00',
            ],
        ],
    ];
}

/** @return array<string, mixed> */
function seo_website_schema(): array
{
    $url = seo_absolute_url();
    if ($url === null) {
        return [];
    }
    return [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => $url . '#website',
        'url' => $url,
        'name' => 'Centro Médico La Quinta',
        'inLanguage' => 'es-CO',
        'publisher' => ['@id' => $url . '#centro-medico-la-quinta'],
    ];
}

/** @param array<string, mixed> $schema */
function seo_schema_json(array $schema): string
{
    if ($schema === []) {
        return '';
    }
    return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
}
