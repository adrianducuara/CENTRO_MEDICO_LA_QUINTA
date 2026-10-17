<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

http_response_code(404);
seo_send_noindex();
$pageTitle = 'Página no encontrada';
$pageDescription = 'La página solicitada no está disponible. Vuelve al inicio de Centro Médico La Quinta para conocer nuestros canales de atención.';
$robots = 'noindex,follow';
require __DIR__ . '/includes/header.php';
?>
<section class="section article-section">
    <div class="container article">
        <p class="eyebrow">Error 404</p>
        <h1>No encontramos esta página</h1>
        <p>Es posible que el enlace haya cambiado o que la página ya no esté disponible.</p>
        <p><a class="button button--blue" href="index.php">Volver al inicio</a></p>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
