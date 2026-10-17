<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/videos.php';

$posts = db()->query("SELECT id, title, content, image_path, created_at FROM posts WHERE status = 'published' ORDER BY created_at DESC, id DESC")->fetchAll();
$videos = public_videos();
$pageTitle = 'Novedades de salud | Centro Médico La Quinta';
$pageDescription = 'Consulte novedades, información institucional y contenidos publicados por Centro Médico La Quinta en Ibagué.';
$canonicalPath = 'posts.php';
$activePage = 'posts';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><p class="eyebrow eyebrow--light">Centro Médico La Quinta</p><h1>Novedades y actualidad</h1><p>Información y noticias para acompañar el cuidado de tu salud.</p></div></section>
<section class="section updates-section">
    <div class="container updates-layout">
        <?php if ($posts === [] && $videos === []): ?>
            <div class="empty-state"><span class="empty-icon" aria-hidden="true">+</span><h2>Pronto compartiremos novedades</h2><p>Vuelve a visitarnos para conocer las noticias del centro médico.</p></div>
        <?php endif; ?>

        <?php if ($posts !== []): ?>
            <header class="updates-heading"><p class="eyebrow">Actualidad</p><h2>Noticias recientes</h2><p>Consulta las publicaciones completas, ordenadas desde la más reciente.</p></header>
            <div class="updates-stream" aria-label="Noticias recientes">
                <?php foreach ($posts as $post): ?>
                <article class="update-item update-item--post">
                    <?php if (!empty($post['image_path'])): ?><div class="update-media"><img src="post-image.php?id=<?= (int) $post['id'] ?>" alt="Imagen de <?= e($post['title']) ?>"></div><?php endif; ?>
                    <div class="update-copy">
                        <p class="card-date"><?= e(format_date($post['created_at'])) ?> <span>·</span> Actualidad</p>
                        <h2><a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h2>
                        <div class="update-content"><?= nl2br(e($post['content'])) ?></div>
                        <a class="text-link" href="post.php?id=<?= (int) $post['id'] ?>">Ver publicación completa <span aria-hidden="true">→</span></a>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($videos !== []): ?>
            <header class="updates-heading updates-heading--videos"><p class="eyebrow">Contenido multimedia</p><h2>Videos y actualizaciones</h2><p>Reproduce los videos y conoce los detalles de cada actualización.</p></header>
            <div class="updates-stream" aria-label="Videos y actualizaciones">
                <?php foreach ($videos as $video): ?>
                <article class="update-item update-item--video">
                    <div class="update-video"><?php render_video_preview($video); ?></div>
                    <div class="update-copy">
                        <p class="card-date"><?= e(format_date($video['created_at'])) ?> <span>·</span> <?= $video['source_type'] === 'local' ? 'Video institucional' : 'Video externo' ?></p>
                        <h2><?= e($video['title']) ?></h2>
                        <?php if (!empty($video['description'])): ?><div class="update-content"><?= nl2br(e($video['description'])) ?></div><?php else: ?><p class="update-content">Consulta este contenido audiovisual de Centro Médico La Quinta.</p><?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
