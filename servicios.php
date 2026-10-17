<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/services.php';

$services = public_services();
$pageTitle = 'Servicios de salud en Ibagué | Centro Médico La Quinta';
$pageDescription = 'Conozca los servicios de atención disponibles en Centro Médico La Quinta, Ibagué, y solicite orientación para su cita.';
$canonicalPath = 'servicios.php';
$activePage = 'services';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero--services"><div class="container"><p class="eyebrow eyebrow--light">Atención integral</p><h1>Servicios para acompañar tu bienestar</h1><p>Conoce las alternativas de atención disponibles y encuentra la orientación que necesitas.</p></div></section>
<section class="section services-section"><div class="container">
    <div class="section-heading"><div><p class="eyebrow">Nuestros servicios</p><h2>Estamos contigo en cada paso</h2></div><p class="section-heading-copy">Nuestro equipo puede orientarte sobre disponibilidad, preparación y agendamiento.</p></div>
    <div class="services-grid">
        <?php foreach ($services as $service): ?>
        <article class="service-card"><span class="service-icon" aria-hidden="true"><?= e($service['icon']) ?></span><h3><?= e($service['title']) ?></h3><p><?= e($service['description']) ?></p><a class="text-link" href="https://wa.me/573168312283?text=Hola%2C%20quisiera%20solicitar%20una%20cita%20para%20<?= rawurlencode($service['title']) ?>." target="_blank" rel="noopener noreferrer">Solicitar cita <span aria-hidden="true">↗</span></a></article>
        <?php endforeach; ?>
    </div>
</div></section>
<section class="service-help"><div class="container service-help-inner"><div><p class="eyebrow eyebrow--light">¿Tienes una pregunta?</p><h2>Te ayudamos a encontrar el servicio indicado.</h2><p>Escríbenos para recibir orientación antes de tu cita.</p></div><a class="button button--light" href="https://wa.me/573168312283?text=Hola%2C%20necesito%20orientaci%C3%B3n%20sobre%20sus%20servicios." target="_blank" rel="noopener noreferrer">Escribir por WhatsApp <span aria-hidden="true">↗</span></a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
