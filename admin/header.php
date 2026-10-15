<?php
$pageTitle = $pageTitle ?? 'Administración';
$notice = flash();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#032e67">
    <title><?= e($pageTitle) ?> | Administración CMQ</title>
    <link rel="stylesheet" href="../public/assets/css/site.css">
    <script src="../public/assets/js/site.js" defer></script>
    <script src="../public/assets/js/password-visibility.js" defer></script>
</head>
<body class="admin-body">
<?php if (empty($publicAuthPage) && !str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/login.php')): ?>
<header class="admin-topbar">
    <a class="admin-brand" href="index.php">Centro Médico <span>La Quinta</span></a>
    <div class="admin-user"><span><?= e($_SESSION['admin_username'] ?? '') ?></span><form action="logout.php" method="post"><?= csrf_field() ?><button class="admin-logout" type="submit">Cerrar sesión</button></form></div>
</header>
<nav class="admin-subnav"><div class="container"><a href="index.php">Panel</a><a href="services.php">Servicios</a><a href="posts.php">Publicaciones</a><a href="documents.php">Documentos PDF</a><a href="pqrsf.php">PQRSF</a><a href="videos.php">Videos</a><a href="configuracion-api.php">Google / Gmail</a><a href="account.php">Mi cuenta / Seguridad</a></div></nav>
<?php endif; ?>
<?php if ($notice !== null): ?><div class="container admin-notices"><div class="admin-alert admin-alert--<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div></div><?php endif; ?>
