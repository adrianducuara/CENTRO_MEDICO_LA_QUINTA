<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/services.php';
require_once __DIR__ . '/../includes/indexnow.php';
require_admin();

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if ($action === 'delete') {
        if (!$id || $id < 1) {
            flash('error', 'El servicio seleccionado no es válido.');
        } else {
            $delete = $pdo->prepare('DELETE FROM servicios WHERE id = :id');
            $delete->execute(['id' => $id]);
            indexnow_notify_paths(['', 'servicios.php']);
            flash('success', 'El servicio se eliminó correctamente.');
        }
        redirect('services.php');
    }

    if ($action !== 'save') {
        http_response_code(400);
        exit('Acción no válida.');
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $icon = trim((string) ($_POST['icon'] ?? '✚'));
    $status = (string) ($_POST['status'] ?? '');
    $sortOrder = filter_var($_POST['sort_order'] ?? null, FILTER_VALIDATE_INT);
    if ($title === '' || text_length($title) > 120 || $description === '' || text_length($description) > 1000 || text_length($icon) > 12 || !in_array($status, ['draft', 'published'], true) || $sortOrder === false || $sortOrder < 0 || $sortOrder > 9999) {
        flash('error', 'Completa título, descripción, icono, orden y estado con valores válidos.');
        redirect($id ? 'services.php?edit=' . $id : 'services.php');
    }

    if ($id && $id > 0) {
        $save = $pdo->prepare('UPDATE servicios SET title = :title, description = :description, icon = :icon, sort_order = :sort_order, status = :status WHERE id = :id');
        $save->execute(['title' => $title, 'description' => $description, 'icon' => $icon, 'sort_order' => $sortOrder, 'status' => $status, 'id' => $id]);
        flash('success', 'El servicio se actualizó correctamente.');
    } else {
        $save = $pdo->prepare('INSERT INTO servicios (title, description, icon, sort_order, status) VALUES (:title, :description, :icon, :sort_order, :status)');
        $save->execute(['title' => $title, 'description' => $description, 'icon' => $icon, 'sort_order' => $sortOrder, 'status' => $status]);
        flash('success', 'El servicio se creó correctamente.');
    }
    indexnow_notify_paths(['', 'servicios.php']);
    redirect('services.php');
}

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId && $editId > 0) {
    $find = $pdo->prepare('SELECT id, title, description, icon, sort_order, status FROM servicios WHERE id = :id');
    $find->execute(['id' => $editId]);
    $editing = $find->fetch() ?: null;
}
$services = $pdo->query('SELECT id, title, icon, sort_order, status FROM servicios ORDER BY sort_order ASC, id ASC')->fetchAll();
$pageTitle = 'Servicios';
require __DIR__ . '/header.php';
?>
<div class="admin-content admin-content--wide">
    <div class="admin-heading"><div><a class="admin-back" href="index.php">← Panel</a><p class="eyebrow">Contenido del sitio</p><h1>Servicios</h1><p>Define los servicios que se muestran en la página pública.</p></div></div>
    <?php if ($notice = flash()): ?><div class="admin-alert admin-alert--<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
    <div class="admin-columns">
        <section class="admin-panel"><h2><?= $editing ? 'Editar servicio' : 'Nuevo servicio' ?></h2><form method="post" action="services.php"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>"><label for="title">Título</label><input id="title" name="title" maxlength="120" value="<?= e($editing['title'] ?? '') ?>" required><label for="description">Descripción</label><textarea id="description" name="description" rows="7" maxlength="1000" required><?= e($editing['description'] ?? '') ?></textarea><div class="form-row"><div><label for="icon">Icono</label><input id="icon" name="icon" maxlength="12" value="<?= e($editing['icon'] ?? '✚') ?>" required></div><div><label for="sort_order">Orden</label><input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="<?= (int) ($editing['sort_order'] ?? 10) ?>" required></div></div><label for="status">Estado</label><select id="status" name="status"><option value="published" <?= ($editing['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Publicado</option><option value="draft" <?= ($editing['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Borrador</option></select><div class="form-actions"><button class="admin-button admin-button--primary" type="submit"><?= $editing ? 'Guardar cambios' : 'Crear servicio' ?></button><?php if ($editing): ?><a class="admin-button admin-button--quiet" href="services.php">Cancelar</a><?php endif; ?></div></form></section>
        <section class="admin-panel"><div class="panel-heading"><h2>Servicios existentes</h2><span class="count-pill"><?= count($services) ?></span></div><?php if ($services === []): ?><p class="panel-empty">Todavía no hay servicios.</p><?php else: ?><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Servicio</th><th>Orden</th><th>Estado</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody><?php foreach ($services as $service): ?><tr><td class="table-title"><span class="service-list-icon" aria-hidden="true"><?= e($service['icon']) ?></span><?= e($service['title']) ?></td><td><?= (int) $service['sort_order'] ?></td><td><span class="status-badge status-badge--<?= e($service['status']) ?>"><?= $service['status'] === 'published' ? 'Publicado' : 'Borrador' ?></span></td><td><div class="table-actions"><a href="services.php?edit=<?= (int) $service['id'] ?>">Editar</a><form method="post" action="services.php" data-confirm="¿Eliminar este servicio? Esta acción no se puede deshacer."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button type="submit" class="text-button text-button--danger">Eliminar</button></form></div></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
