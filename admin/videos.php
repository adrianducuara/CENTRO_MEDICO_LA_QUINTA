<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/videos.php';
require_once __DIR__ . '/../includes/indexnow.php';
require_admin();

$pdo = db();
$errors = [];
$values = ['title' => '', 'description' => '', 'source_type' => 'local', 'external_source' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            flash('error', 'El video seleccionado no es válido.');
            redirect('videos.php');
        }
        $find = $pdo->prepare('SELECT source_type, source_value FROM videos WHERE id = :id');
        $find->execute(['id' => $id]);
        $video = $find->fetch();
        if ($video === false) {
            flash('error', 'No se encontró el video que intentas eliminar.');
            redirect('videos.php');
        }
        if ($video['source_type'] === 'local' && preg_match('/\A[a-f0-9]{32}\.(mp4|webm|ogg)\z/', $video['source_value'])) {
            $path = VIDEO_STORAGE . DIRECTORY_SEPARATOR . $video['source_value'];
            if (is_file($path) && !unlink($path)) {
                flash('error', 'No fue posible eliminar el archivo del video.');
                redirect('videos.php');
            }
        }
        $delete = $pdo->prepare('DELETE FROM videos WHERE id = :id');
        $delete->execute(['id' => $id]);
        indexnow_notify_paths(['', 'posts.php']);
        flash('success', 'El video se eliminó correctamente.');
        redirect('videos.php');
    }

    if ($action !== 'save') {
        http_response_code(400);
        exit('Acción no válida.');
    }
    $values['title'] = trim((string) ($_POST['title'] ?? ''));
    $values['description'] = trim((string) ($_POST['description'] ?? ''));
    $values['source_type'] = (string) ($_POST['source_type'] ?? 'local');
    $values['external_source'] = trim((string) ($_POST['external_source'] ?? ''));
    if ($values['title'] === '' || text_length($values['title']) > 180) {
        $errors['title'] = 'Escribe un título de hasta 180 caracteres.';
    }
    if (text_length($values['description']) > 3000) {
        $errors['description'] = 'La descripción puede tener como máximo 3000 caracteres.';
    }
    if (!in_array($values['source_type'], ['local', 'external'], true)) {
        $errors['source_type'] = 'Selecciona un tipo de origen válido.';
    }

    $sourceValue = '';
    $mimeType = null;
    $fileSize = null;
    $destination = null;
    if ($values['source_type'] === 'external') {
        $sourceValue = normalize_external_video_source($values['external_source']) ?? '';
        if ($sourceValue === '') {
            $errors['external_source'] = 'Usa una URL HTTPS o iframe de YouTube o Vimeo válido.';
        }
    } else {
        $file = $_FILES['video_file'] ?? null;
        if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['name'], $file['size']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errors['video_file'] = 'Selecciona un archivo de video válido.';
        } elseif (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] < 1 || (int) $file['size'] > VIDEO_MAX_SIZE) {
            $errors['video_file'] = 'El archivo debe tener un tamaño máximo de 100 MB.';
        } else {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedTypes = allowed_video_mime_types();
            $detectedMime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $isOgg = $extension === 'ogg' && in_array($detectedMime, ['video/ogg', 'application/ogg'], true);
            if (!isset($allowedTypes[$extension]) || ($detectedMime !== $allowedTypes[$extension] && !$isOgg)) {
                $errors['video_file'] = 'Solo se permiten videos MP4, WebM u OGG con un tipo MIME válido.';
            } else {
                $sourceValue = bin2hex(random_bytes(16)) . '.' . $extension;
                $mimeType = $allowedTypes[$extension];
                $fileSize = (int) $file['size'];
                if (!is_dir(VIDEO_STORAGE) && !mkdir(VIDEO_STORAGE, 0750, true) && !is_dir(VIDEO_STORAGE)) {
                    throw new RuntimeException('No fue posible preparar el almacenamiento de videos.');
                }
                $destination = VIDEO_STORAGE . DIRECTORY_SEPARATOR . $sourceValue;
                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new RuntimeException('No fue posible guardar el video recibido.');
                }
            }
        }
    }

    if ($errors !== [] && $destination !== null && is_file($destination)) {
        unlink($destination);
    }
    if ($errors === []) {
        try {
            $insert = $pdo->prepare('INSERT INTO videos (title, description, source_type, source_value, mime_type, file_size) VALUES (:title, :description, :source_type, :source_value, :mime_type, :file_size)');
            $insert->execute(['title' => $values['title'], 'description' => $values['description'] !== '' ? $values['description'] : null, 'source_type' => $values['source_type'], 'source_value' => $sourceValue, 'mime_type' => $mimeType, 'file_size' => $fileSize]);
        } catch (PDOException $exception) {
            if ($destination !== null && is_file($destination)) {
                unlink($destination);
            }
            flash('error', 'No fue posible guardar el video. Verifica que el enlace no esté registrado.');
            redirect('videos.php');
        }
        indexnow_notify_paths(['', 'posts.php']);
        flash('success', 'El video se guardó correctamente.');
        redirect('videos.php');
    }
}

$videos = $pdo->query('SELECT id, title, description, source_type, source_value, mime_type, file_size, created_at FROM videos ORDER BY created_at DESC, id DESC')->fetchAll();
$pageTitle = 'Videos';
require __DIR__ . '/header.php';
?>
<div class="admin-content admin-content--wide">
    <div class="admin-heading"><div><a class="admin-back" href="index.php">← Panel</a><p class="eyebrow">Contenido del sitio</p><h1>Videos</h1><p>Carga y administra los videos e incrustaciones del sitio web.</p></div></div>
    <div class="admin-columns admin-columns--videos">
        <section class="admin-panel"><h2>Nuevo video</h2><p class="panel-description">Admite MP4, WebM u OGG de hasta 100 MB, o enlaces de YouTube y Vimeo.</p>
            <?php if ($errors !== []): ?><div class="admin-alert admin-alert--error" role="alert">Revisa los campos señalados antes de guardar el video.</div><?php endif; ?>
            <form method="post" action="videos.php" enctype="multipart/form-data" id="video-form"><?= csrf_field() ?><input type="hidden" name="action" value="save">
                <label for="title">Título o descripción corta</label><input id="title" name="title" maxlength="180" value="<?= e($values['title']) ?>" required><?php if (isset($errors['title'])): ?><span class="field-error"><?= e($errors['title']) ?></span><?php endif; ?>
                <label for="description">Descripción detallada <span class="field-hint">Opcional · hasta 3000 caracteres</span></label><textarea id="description" name="description" rows="5" maxlength="3000" placeholder="Explica el contenido del video para el público."><?= e($values['description']) ?></textarea><?php if (isset($errors['description'])): ?><span class="field-error"><?= e($errors['description']) ?></span><?php endif; ?>
                <fieldset class="source-options"><legend>Tipo de origen</legend><label><input type="radio" name="source_type" value="local" <?= $values['source_type'] === 'local' ? 'checked' : '' ?>> Archivo local</label><label><input type="radio" name="source_type" value="external" <?= $values['source_type'] === 'external' ? 'checked' : '' ?>> Enlace o iframe</label></fieldset>
                <div class="video-source-field" data-video-source="local"><label for="video_file">Archivo de video</label><input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/ogg,.mp4,.webm,.ogg"><?php if (isset($errors['video_file'])): ?><span class="field-error"><?= e($errors['video_file']) ?></span><?php endif; ?></div>
                <div class="video-source-field" data-video-source="external"><label for="external_source">URL o código iframe</label><textarea id="external_source" name="external_source" rows="4" placeholder="https://www.youtube.com/watch?v=…"><?= e($values['external_source']) ?></textarea><span class="field-hint">Solo se aceptan enlaces HTTPS de YouTube o Vimeo.</span><?php if (isset($errors['external_source'])): ?><span class="field-error"><?= e($errors['external_source']) ?></span><?php endif; ?></div>
                <div class="video-preview-box"><span class="video-preview-label">Previsualización</span><div id="video-form-preview"><p>Selecciona un archivo o pega una URL para ver la previsualización.</p></div></div>
                <button class="admin-button admin-button--primary" type="submit">Guardar video <span aria-hidden="true">↑</span></button>
            </form>
        </section>
        <section class="admin-panel"><div class="panel-heading"><h2>Videos existentes</h2><span class="count-pill"><?= count($videos) ?></span></div>
            <?php if ($videos === []): ?><p class="panel-empty">Todavía no hay videos registrados.</p><?php else: ?><div class="video-grid">
                <?php foreach ($videos as $video): ?><article class="video-card"><div class="video-card-preview"><?php render_video_preview($video, '../'); ?></div><div class="video-card-body"><p class="card-date"><?= e(format_date($video['created_at'])) ?> <span>·</span> <?= $video['source_type'] === 'local' ? 'Archivo local' : 'Enlace externo' ?></p><h3><?= e($video['title']) ?></h3><?php if (!empty($video['description'])): ?><p class="video-card-description"><?= e(post_excerpt($video['description'], 110)) ?></p><?php endif; ?><div class="video-card-actions"><button class="text-button" type="button" data-copy-video data-video-code="<?= e(video_embed_code($video)) ?>">Copiar código</button><form method="post" action="videos.php" data-confirm="¿Eliminar este video? Si es local, el archivo también se eliminará."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $video['id'] ?>"><button type="submit" class="text-button text-button--danger">Eliminar</button></form></div></div></article><?php endforeach; ?>
            </div><?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
