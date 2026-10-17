<?php
declare(strict_types=1);

putenv('APP_URL=https://centromedicolquinta.test');
require_once __DIR__ . '/../includes/seo.php';

function seo_test(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FALLO: {$message}\n");
        exit(1);
    }
    echo "OK: {$message}\n";
}

seo_test(seo_site_url() === 'https://centromedicolquinta.test', 'APP_URL produce un origen canónico HTTPS');
seo_test(seo_absolute_url('servicios.php') === 'https://centromedicolquinta.test/servicios.php', 'las URLs públicas son absolutas y estables');

$schema = seo_medical_clinic_schema();
seo_test(($schema['@type'] ?? null) === ['MedicalClinic', 'LocalBusiness'], 'el Schema usa los tipos de entidad previstos');
seo_test(($schema['url'] ?? null) === 'https://centromedicolquinta.test/', 'el Schema usa una URL de entidad canónica sin doble barra');
seo_test(!array_key_exists('aggregateRating', $schema), 'el Schema no declara calificaciones no verificadas');
seo_test(str_contains(seo_schema_json($schema), 'Centro Médico La Quinta'), 'el Schema se serializa como JSON válido');

echo "Pruebas SEO unitarias superadas.\n";
