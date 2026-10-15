<?php
declare(strict_types=1);

/**
 * Production-only values for shared hosting where PHP environment variables
 * are not exposed to the application. The local file is deliberately not
 * versioned and follows the same pattern as database.local.php.
 *
 * @return array<string, mixed>
 */
function runtime_config(): array
{
    static $config;
    if (is_array($config)) {
        return $config;
    }

    $path = dirname(__DIR__) . '/config/runtime.local.php';
    $loaded = is_file($path) ? require $path : [];
    if (!is_array($loaded)) {
        throw new RuntimeException('La configuración local de producción no es válida.');
    }

    $config = $loaded;
    return $config;
}

function runtime_config_value(string $key): string
{
    $environmentValue = getenv($key);
    if (is_string($environmentValue) && $environmentValue !== '') {
        return $environmentValue;
    }

    $localValue = runtime_config()[strtolower($key)] ?? '';
    return is_string($localValue) ? $localValue : '';
}
