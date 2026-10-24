<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/post_images.php';
require_once __DIR__ . '/../includes/indexnow.php';
require_admin();

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            flash('error', 'La publicación seleccionada no es válida.');
            redirect('posts.php');
        }
        $find = $pdo->prepare('SELECT image_path FROM posts WHERE id = :id');
        $find->execute(['id' => $id]);
        $post = $find->fetch();
        if ($post === false) {
            flash('error', 'No se encontró la publicación que intentas eliminar.');
            redirect('posts.php');
        }
        $delete = $pdo->prepare('DELETE FROM posts WHERE id = :id');
        $delete->execute(['id' => $id]);
        remove_post_image(is_string($post['image_path']) ? $post['image_path'] : null);
        indexnow_notify_paths(['', 'posts.php', 'post.php?id=' . $id]);
        flash('success', 'La publicación se eliminó correctamente.');
        redirect('posts.php');
    }

    if ($action !== 'save') {
        http_response_code(400);
        exit('Acción no válida.');
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $status = (string) ($_POST['status'] ?? '');
    if ($title === '' || text_length($title) > 180 || $content === '' || !in_array($status, ['draft', 'published'], true)) {
        flash('error', 'Completa un título de hasta 180 caracteres, el contenido y un estado válido.');
        redirect($id ? 'posts.php?edit=' . $id : 'posts.php');
    }

    $existing = null;
    if ($id && $id > 0) {
        $find = $pdo->prepare('SELECT id, image_path, image_mime FROM posts WHERE id = :id');
        $find->execute(['id' => $id]);
        $existing = $find->fetch() ?: null;
        if ($existing === null) {
            flash('error', 'No se encontró la publicación que intentas editar.');
            redirect('posts.php');
        }
    }

    $image = $_FILES['image'] ?? null;
    $hasNewImage = is_array($image) && (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
    $newImage = null;
    if ($hasNewImage) {
        try {
            /** @var array{name:mixed,type:mixed,tmp_name:mixed,error:mixed,size:mixed} $image */
            $newImage = store_post_image($image);
        } catch (InvalidArgumentException | RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect($id ? 'posts.php?edit=' . $id : 'posts.php');
        }
    }

    $removeCurrent = (($_POST['remove_image'] ?? '') === '1');
    $previousPath = $existing !== null && is_string($existing['image_path']) ? $existing['image_path'] : null;
    $nextPath = $newImage['path'] ?? ($removeCurrent ? null : $previousPath);
    $nextMime = $newImage['mime'] ?? ($removeCurrent ? null : ($existing['image_mime'] ?? null));

    try {
        if ($existing !== null) {
            $save = $pdo->prepare('UPDATE posts SET title = :title, content = :content, status = :status, image_path = :image_path, image_mime = :image_mime WHERE id = :id');
            $save->execute(['title' => $title, 'content' => $content, 'status' => $status, 'image_path' => $nextPath, 'image_mime' => $nextMime, 'id' => $id]);
        } else {
            $save = $pdo->prepare('INSERT INTO posts (title, content, status, image_path, image_mime) VALUES (:title, :content, :status, :image_path, :image_mime)');
            $save->execute(['title' => $title, 'content' => $content, 'status' => $status, 'image_path' => $nextPath, 'image_mime' => $nextMime]);
        }
    } catch (PDOException $exception) {
        if ($newImage !== null) {
            remove_post_image($newImage['path']);
        }
        throw $exception;
    }

    if ($previousPath !== null && $previousPath !== $nextPath) {
        remove_post_image($previousPath);
    }
    $postId = $existing !== null ? (int) $existing['id'] : (int) $pdo->lastInsertId();
    indexnow_notify_paths(['', 'posts.php', 'post.php?id=' . $postId]);
    flash('success', $existing !== null ? 'La publicación se actualizó correctamente.' : 'La publicación se creó correctamente.');
    redirect('posts.php');
}

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $find = $pdo->prepare('SELECT id, title, content, status, image_path, image_mime FROM posts WHERE id = :id');
    $find->execute(['id' => $editId]);
    $editing = $find->fetch() ?: null;
}
$posts = $pdo->query('SELECT id, title, status, image_path, created_at FROM posts ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Publicaciones';
require __DIR__ . '/header.php';
?>
<div class="admin-content admin-content--wide">
    <div class="admin-heading"><div><a class="admin-back" href="index.php">← Panel</a><p class="eyebrow">Contenido del sitio</p><h1>Publicaciones</h1><p>Crea y administra las novedades visibles para todos.</p></div></div>
    <?php if ($notice = flash()): ?><div class="admin-alert admin-alert--<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
    <div class="admin-columns">
        <section class="admin-panel">
            <h2><?= $editing ? 'Editar publicación' : 'Nueva publicación' ?></h2>
            <form id="post-form" method="post" action="posts.php" enctype="multipart/form-data">
                <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
                <label for="title">Título</label><input id="title" name="title" maxlength="180" value="<?= e($editing['title'] ?? '') ?>" required>
                <label for="content">Contenido <span class="field-hint">Texto plano</span></label><textarea id="content" name="content" rows="11" required><?= e($editing['content'] ?? '') ?></textarea>
                <label for="image">Imagen destacada / Adjunta <span class="field-hint">Opcional · hasta 10 MB</span></label>
                <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp,.gif,.svg,image/jpeg,image/png,image/webp,image/gif,image/svg+xml">
                <?php $currentImageUrl = $editing !== null && !empty($editing['image_path']) ? '../post-image.php?id=' . (int) $editing['id'] : ''; ?>
                <div id="post-image-preview" class="post-image-preview" data-current-image="<?= e($currentImageUrl) ?>">
                    <?php if ($currentImageUrl !== ''): ?><img src="<?= e($currentImageUrl) ?>" alt="Imagen actual de la publicación"><?php else: ?><span>Sin imagen seleccionada</span><?php endif; ?>
                </div>
                <div class="post-image-actions"><button type="button" class="admin-button admin-button--quiet" data-clear-post-image>Quitar selección</button><?php if ($currentImageUrl !== ''): ?><label class="check-label"><input id="remove-image" name="remove_image" type="checkbox" value="1"> Eliminar imagen actual</label><?php endif; ?></div>
                <label for="status">Estado</label><select id="status" name="status"><option value="draft" <?= ($editing['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Borrador</option><option value="published" <?= ($editing['status'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option></select>
                <div class="form-actions"><button class="admin-button admin-button--primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Crear publicación' ?></button><?php if ($editing): ?><a class="admin-button admin-button--quiet" href="posts.php">Cancelar</a><?php endif; ?></div>
            </form>
        </section>
        <section class="admin-panel">
            <div class="panel-heading"><h2>Publicaciones existentes</h2><span class="count-pill"><?= count($posts) ?></span></div>
            <?php if ($posts === []): ?><p class="panel-empty">Todavía no hay publicaciones.</p><?php else: ?>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Imagen</th><th>Título</th><th>Estado</th><th>Fecha</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                <?php foreach ($posts as $post): ?><tr><td class="post-thumbnail-cell"><?php if (!empty($post['image_path'])): ?><img class="post-thumbnail" src="../post-image.php?id=<?= (int) $post['id'] ?>" alt=""><?php else: ?><span class="post-thumbnail-placeholder" aria-label="Sin imagen">—</span><?php endif; ?></td><td class="table-title"><?= e($post['title']) ?></td><td><span class="status-badge status-badge--<?= e($post['status']) ?>"><?= $post['status'] === 'published' ? 'Publicado' : 'Borrador' ?></span></td><td><?= e(format_date($post['created_at'])) ?></td><td><div class="table-actions"><a href="posts.php?edit=<?= (int) $post['id'] ?>">Editar</a><form method="post" action="posts.php" data-confirm="¿Eliminar esta publicación? Esta acción no se puede deshacer."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button type="submit" class="text-button text-button--danger">Eliminar</button></form></div></td></tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
