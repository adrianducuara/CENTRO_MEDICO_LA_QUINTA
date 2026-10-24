<?php
$pageTitle = $pageTitle ?? 'Centro Médico La Quinta';
$pageDescription = $pageDescription ?? 'Centro Médico La Quinta en Ibagué. Conozca nuestros servicios, horarios y canales de atención.';
$activePage = $activePage ?? '';
$robots = $robots ?? 'index,follow';
$canonicalPath = $canonicalPath ?? basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
$canonicalUrl = seo_absolute_url($canonicalPath);
$structuredData = $structuredData ?? [];
$openGraphType = $openGraphType ?? 'website';
$socialImagePath = $socialImagePath ?? 'public/assets/images/fachada-clinica.jpeg';
$socialImageUrl = seo_absolute_url($socialImagePath);
$socialImageAlt = $socialImageAlt ?? 'Fachada de Centro Médico La Quinta en Ibagué';
$socialImageDimensions = $socialImageDimensions ?? [1365, 768];
$socialImageType = $socialImageType ?? 'image/jpeg';
$notice = flash();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <?php if ($canonicalUrl !== null): ?><link rel="canonical" href="<?= e($canonicalUrl) ?>">
    <meta property="og:locale" content="es_CO">
    <meta property="og:type" content="<?= e($openGraphType) ?>">
    <meta property="og:site_name" content="Centro Médico La Quinta">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonicalUrl) ?>">
    <?php if ($socialImageUrl !== null): ?>
    <meta property="og:image" content="<?= e($socialImageUrl) ?>">
    <meta property="og:image:secure_url" content="<?= e($socialImageUrl) ?>">
    <?php if (is_string($socialImageType)): ?><meta property="og:image:type" content="<?= e($socialImageType) ?>">
    <?php endif; ?><?php if (is_array($socialImageDimensions) && isset($socialImageDimensions[0], $socialImageDimensions[1]) && is_int($socialImageDimensions[0]) && is_int($socialImageDimensions[1])): ?><meta property="og:image:width" content="<?= $socialImageDimensions[0] ?>">
    <meta property="og:image:height" content="<?= $socialImageDimensions[1] ?>">
    <?php endif; ?><meta property="og:image:alt" content="<?= e($socialImageAlt) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <?php if ($socialImageUrl !== null): ?><meta name="twitter:image" content="<?= e($socialImageUrl) ?>">
    <meta name="twitter:image:alt" content="<?= e($socialImageAlt) ?>">
    <?php endif; ?>
    <?php endif; ?>
    <link rel="icon" href="public/assets/images/cmq-site-icon.jpeg">
    <link rel="stylesheet" href="public/assets/css/site.css">
    <link rel="stylesheet" href="public/assets/css/carousel.css">
    <script src="public/assets/js/site.js" defer></script>
    <script src="public/assets/js/carousel.js" defer></script>
    <script src="public/assets/js/typewriter.js" defer></script>
    <?php foreach (($extraScripts ?? []) as $script): ?><script src="<?= e($script) ?>" defer></script><?php endforeach; ?>
    <?php foreach ($structuredData as $schema): ?><?php if (is_array($schema) && $schema !== []): ?><script type="application/ld+json"><?= seo_schema_json($schema) ?></script><?php endif; ?><?php endforeach; ?>
</head>
<body>
<a class="skip-link" href="#main">Saltar al contenido</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="index.php" aria-label="Centro Médico La Quinta, inicio">
            <img src="public/assets/images/cmq-clinic-logo-transparent.png" alt="Clínica Integral Provida S.A.S. — Centro Médico La Quinta" width="2169" height="725">
        </a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-navigation">
            <span class="menu-icon" aria-hidden="true"></span>
            <span>Menú</span>
        </button>
        <nav class="site-nav" id="site-navigation" aria-label="Navegación principal">
            <a class="<?= $activePage === 'home' ? 'is-active' : '' ?>" href="index.php">Inicio</a>
            <a class="<?= $activePage === 'about' ? 'is-active' : '' ?>" href="nosotros.php">Nosotros</a>
            <a class="<?= $activePage === 'services' ? 'is-active' : '' ?>" href="servicios.php">Servicios</a>
            <a class="<?= $activePage === 'pqrsf' ? 'is-active' : '' ?>" href="pqrsf.php">PQRSF</a>
        </nav>
        <a class="button button--whatsapp" href="https://wa.me/573168312283" target="_blank" rel="noopener noreferrer">
            <svg class="whatsapp-icon" viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path d="M19.1 17.5c-.2-.1-1.4-.7-1.6-.8-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1-1.4-.7-2.4-1.6-3.3-3-.2-.2 0-.4.1-.5.1-.1.3-.4.4-.5.1-.2.1-.3 0-.5l-.7-1.6c-.1-.4-.3-.3-.5-.3h-.5c-.2 0-.5.1-.7.3-.3.3-.9.9-.9 2.2 0 1.3.9 2.5 1 2.7 1 1.5 2.5 2.7 4.2 3.4.4.2.8.3 1.1.4.5.2 1 .2 1.4.1.4-.1 1.4-.6 1.6-1.2.2-.6.2-1.1.1-1.2-.1-.1-.3-.2-.5-.3Z" fill="currentColor"/><path d="M16 3.5a12.4 12.4 0 0 0-10.6 18.9L4 28.5l6.3-1.6A12.5 12.5 0 1 0 16 3.5Zm0 22.8c-1.9 0-3.7-.5-5.3-1.5l-.4-.2-3.7 1 .9-3.6-.2-.4A10.3 10.3 0 1 1 16 26.3Z" fill="currentColor"/></svg> Solicitar cita
        </a>
    </div>
</header>
<main id="main">
<?php if ($notice !== null): ?>
    <div class="container"><div class="notice notice--<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div></div>
<?php endif; ?>
