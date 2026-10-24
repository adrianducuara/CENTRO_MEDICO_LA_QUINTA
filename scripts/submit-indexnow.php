<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo disponible desde la línea de comandos.');
}

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/indexnow.php';

$paths = ['', 'nosotros.php', 'servicios.php', 'posts.php', 'documents.php'];
try {
    $posts = db()->query("SELECT id FROM posts WHERE status = 'published' ORDER BY id ASC")->fetchAll();
    foreach ($posts as $post) {
        $paths[] = 'post.php?id=' . (int) $post['id'];
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "No fue posible leer las publicaciones públicas: " . $exception->getMessage() . "\n");
    exit(1);
}

if (!indexnow_notify_paths($paths)) {
    if (indexnow_is_rate_limited()) {
        fwrite(STDERR, "IndexNow aplicó un límite de frecuencia. Espera 24 horas antes de ejecutar este comando otra vez.\n");
        exit(1);
    }
    fwrite(STDERR, "IndexNow no confirmó la recepción. Revisa la clave pública, el registro PHP y la conectividad saliente.\n");
    exit(1);
}

echo "IndexNow recibió " . count($paths) . " URLs públicas.\n";
