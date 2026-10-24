<?php
declare(strict_types=1);

putenv('APP_URL=https://centromedicolquinta.test');
require_once __DIR__ . '/../includes/indexnow.php';

function indexnow_test(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FALLO: {$message}\n");
        exit(1);
    }
    echo "OK: {$message}\n";
}

$payload = indexnow_payload([
    'https://centromedicolquinta.test/',
    'https://centromedicolquinta.test/servicios.php',
    'https://other.example/should-not-be-submitted',
    'https://centromedicolquinta.test:8443/should-not-be-submitted',
    'https://centromedicolquinta.test/servicios.php',
]);

indexnow_test($payload !== null, 'se construye un payload con APP_URL y clave válidas');
indexnow_test(($payload['host'] ?? null) === 'centromedicolquinta.test', 'el payload usa exclusivamente el host canónico');
indexnow_test(($payload['keyLocation'] ?? null) === 'https://centromedicolquinta.test/' . INDEXNOW_KEY_FILE, 'el payload declara la clave en la raíz HTTPS');
indexnow_test(($payload['urlList'] ?? []) === [
    'https://centromedicolquinta.test/',
    'https://centromedicolquinta.test/servicios.php',
], 'el payload descarta URLs externas y duplicadas');
indexnow_test(INDEXNOW_RATE_LIMIT_SECONDS === 86400, 'un HTTP 429 activa una espera local de 24 horas');

echo "Pruebas IndexNow unitarias superadas.\n";
