<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$documents = db()->query('SELECT id, title, original_name, file_size, created_at FROM documentos_pdf ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Documentos públicos | Centro Médico La Quinta';
$pageDescription = 'Consulte los documentos públicos disponibles de Centro Médico La Quinta en Ibagué.';
$canonicalPath = 'documents.php';
$activePage = 'documents';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero"><div class="container"><p class="eyebrow eyebrow--light">Información para ti</p><h1>Documentos</h1><p>Consulta y descarga los documentos publicados por nuestro equipo.</p></div></section>
<section class="section"><div class="container">
    <?php if ($documents === []): ?>
        <div class="empty-state"><span class="empty-icon" aria-hidden="true">↓</span><h2>Aún no hay documentos publicados</h2><p>Cuando haya documentos disponibles, podrás encontrarlos aquí.</p></div>
    <?php else: ?>
        <div class="document-list">
            <?php foreach ($documents as $document): ?>
            <article class="document-card"><span class="pdf-icon" aria-hidden="true">PDF</span><div class="document-info"><h2><?= e($document['title']) ?></h2><p><?= e(format_date($document['created_at'])) ?> <span>·</span> <?= number_format((int) $document['file_size'] / 1048576, 1, ',', '.') ?> MB</p></div><div class="document-actions"><a class="button button--soft" href="download.php?id=<?= (int) $document['id'] ?>&amp;view=1" target="_blank" rel="noopener noreferrer">Ver <span aria-hidden="true">↗</span></a><a class="button button--blue" href="download.php?id=<?= (int) $document['id'] ?>" aria-label="Descargar <?= e($document['title']) ?>">Descargar <span aria-hidden="true">↓</span></a></div></article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
