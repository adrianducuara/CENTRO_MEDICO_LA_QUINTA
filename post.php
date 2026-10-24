<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(404);
    $post = null;
} else {
    $query = db()->prepare("SELECT id, title, content, image_path, created_at, updated_at FROM posts WHERE id = :id AND status = 'published'");
    $query->execute(['id' => $id]);
    $post = $query->fetch() ?: null;
    if ($post === null) {
        http_response_code(404);
    }
}
$pageTitle = $post === null ? 'Artículo no encontrado | Centro Médico La Quinta' : $post['title'] . ' | Centro Médico La Quinta';
$pageDescription = $post === null
    ? 'La publicación solicitada no está disponible. Consulte las novedades de Centro Médico La Quinta.'
    : post_excerpt((string) $post['content'], 155);
$canonicalPath = $post === null ? 'post.php' : 'post.php?id=' . (int) $post['id'];
$robots = $post === null ? 'noindex,follow' : 'index,follow';
if ($post === null) {
    seo_send_noindex();
}
$openGraphType = $post === null ? 'website' : 'article';
if ($post !== null && !empty($post['image_path'])) {
    $socialImagePath = 'post-image.php?id=' . (int) $post['id'];
    $socialImageAlt = 'Imagen de ' . (string) $post['title'];
    // User-uploaded images have variable dimensions, so no fixed dimensions
    // or MIME type are declared for their social preview.
    $socialImageDimensions = false;
    $socialImageType = false;
}
$structuredData = [];
if ($post !== null) {
    $articleUrl = seo_absolute_url($canonicalPath);
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => (string) $post['title'],
        'datePublished' => date(DATE_ATOM, strtotime((string) $post['created_at']) ?: time()),
        'dateModified' => date(DATE_ATOM, strtotime((string) $post['updated_at']) ?: time()),
        'mainEntityOfPage' => $articleUrl === null ? null : ['@type' => 'WebPage', '@id' => $articleUrl],
        'publisher' => seo_absolute_url() === null ? null : ['@id' => seo_absolute_url() . '#centro-medico-la-quinta'],
    ];
    if (!empty($post['image_path']) && $articleUrl !== null) {
        $articleSchema['image'] = seo_absolute_url('post-image.php?id=' . (int) $post['id']);
    }
    $structuredData[] = array_filter($articleSchema, static fn ($value): bool => $value !== null);
}
$activePage = 'posts';
require __DIR__ . '/includes/header.php';
?>
<section class="section article-section">
    <article class="container article">
        <?php if ($post === null): ?>
            <p class="eyebrow">Novedades</p><h1>No encontramos este artículo</h1><p>Es posible que haya sido retirado o que el enlace no sea correcto.</p><a class="button button--blue" href="posts.php">Volver a novedades</a>
        <?php else: ?>
            <a class="back-link" href="posts.php">← Todas las novedades</a>
            <p class="eyebrow">Actualidad · <?= e(format_date($post['created_at'])) ?></p>
            <h1><?= e($post['title']) ?></h1>
            <?php if (!empty($post['image_path'])): ?><img class="article-featured-image" src="post-image.php?id=<?= (int) $post['id'] ?>" alt="Imagen de <?= e($post['title']) ?>"><?php endif; ?>
            <div class="article-content"><?= nl2br(e($post['content'])) ?></div>
        <?php endif; ?>
    </article>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
