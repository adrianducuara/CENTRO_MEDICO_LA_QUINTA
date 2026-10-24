<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/indexnow.php';
require_admin();

$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            flash('error', 'El documento seleccionado no es válido.');
            redirect('documents.php');
        }
        $find = $pdo->prepare('SELECT stored_name FROM documentos_pdf WHERE id = :id');
        $find->execute(['id' => $id]);
        $document = $find->fetch();
        if ($document === false) {
            flash('error', 'No se encontró el documento que intentas eliminar.');
            redirect('documents.php');
        }
        $delete = $pdo->prepare('DELETE FROM documentos_pdf WHERE id = :id');
        $delete->execute(['id' => $id]);
        if (preg_match('/\A[a-f0-9]{32}\.pdf\z/', $document['stored_name'])) {
            $path = PDF_STORAGE . DIRECTORY_SEPARATOR . $document['stored_name'];
            if (is_file($path) && !unlink($path)) {
                throw new RuntimeException('No fue posible eliminar el archivo PDF del almacenamiento.');
            }
        }
        indexnow_notify_paths(['documents.php']);
        flash('success', 'El documento se eliminó correctamente.');
        redirect('documents.php');
    }
    if ($action !== 'upload') {
        http_response_code(400);
        exit('Acción no válida.');
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $file = $_FILES['pdf'] ?? null;
    if ($title === '' || text_length($title) > 180) {
        flash('error', 'Escribe un título de hasta 180 caracteres.');
        redirect('documents.php');
    }
    if (!is_array($file) || !isset($file['error'], $file['tmp_name'], $file['name'], $file['size'])) {
        flash('error', 'Selecciona un archivo PDF para subir.');
        redirect('documents.php');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
            ? 'El archivo supera el límite permitido de 40 MB.'
            : 'No se pudo recibir el archivo. Inténtalo de nuevo.';
        flash('error', $message);
        redirect('documents.php');
    }
    if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] > PDF_MAX_SIZE || (int) $file['size'] < 5) {
        flash('error', 'El archivo debe ser un PDF válido de hasta 40 MB.');
        redirect('documents.php');
    }
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        flash('error', 'Solo se permiten archivos con extensión PDF.');
        redirect('documents.php');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    if ($finfo->file($file['tmp_name']) !== 'application/pdf') {
        flash('error', 'El tipo de archivo no es un PDF válido.');
        redirect('documents.php');
    }
    $handle = fopen($file['tmp_name'], 'rb');
    $signature = $handle === false ? false : fread($handle, 5);
    if (is_resource($handle)) {
        fclose($handle);
    }
    if ($signature !== '%PDF-') {
        flash('error', 'El contenido del archivo no tiene una firma PDF válida.');
        redirect('documents.php');
    }

    $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', basename(str_replace('\\', '/', $file['name']))) ?? 'documento.pdf';
    if (text_length($originalName) > 255) {
        flash('error', 'El nombre original del archivo es demasiado largo.');
        redirect('documents.php');
    }
    $storedName = bin2hex(random_bytes(16)) . '.pdf';
    if (!is_dir(PDF_STORAGE) && !mkdir(PDF_STORAGE, 0750, true) && !is_dir(PDF_STORAGE)) {
        throw new RuntimeException('No fue posible preparar el almacenamiento de documentos.');
    }
    $destination = PDF_STORAGE . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('No fue posible guardar el PDF recibido.');
    }
    try {
        $insert = $pdo->prepare('INSERT INTO documentos_pdf (title, original_name, stored_name, file_size) VALUES (:title, :original_name, :stored_name, :file_size)');
        $insert->execute([
            'title' => $title,
            'original_name' => $originalName,
            'stored_name' => $storedName,
            'file_size' => (int) $file['size'],
        ]);
    } catch (PDOException $exception) {
        if (is_file($destination) && !unlink($destination)) {
            error_log('No fue posible retirar el PDF huérfano tras un error de base de datos: ' . $destination);
        }
        throw $exception;
    }
    indexnow_notify_paths(['documents.php']);
    flash('success', 'El PDF se publicó correctamente.');
    redirect('documents.php');
}

$documents = $pdo->query('SELECT id, title, original_name, file_size, created_at FROM documentos_pdf ORDER BY created_at DESC')->fetchAll();
$pageTitle = 'Documentos PDF';
require __DIR__ . '/header.php';
?>
<div class="admin-content admin-content--wide">
    <div class="admin-heading"><div><a class="admin-back" href="index.php">← Panel</a><p class="eyebrow">Contenido del sitio</p><h1>Documentos PDF</h1><p>Sube y administra documentos para que estén disponibles públicamente.</p></div></div>
    <?php if ($notice = flash()): ?><div class="admin-alert admin-alert--<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div><?php endif; ?>
    <div class="admin-columns admin-columns--documents">
        <section class="admin-panel">
            <h2>Subir un documento</h2><p class="panel-description">PDF únicamente, hasta 40 MB. El archivo se guardará con un nombre aleatorio.</p>
            <form id="document-form" method="post" action="documents.php" enctype="multipart/form-data">
                <?= csrf_field() ?><input type="hidden" name="action" value="upload">
                <label for="title">Nombre visible</label><input id="title" name="title" maxlength="180" placeholder="Ej. Portafolio de servicios" required>
                <label for="pdf">Archivo PDF</label><input id="pdf" name="pdf" type="file" accept=".pdf,application/pdf" required><span class="field-hint">Máximo 40 MB · PDF</span><span id="pdf-size-error" class="field-error" hidden>El archivo supera el límite permitido de 40 MB.</span>
                <button class="admin-button admin-button--primary" type="submit">Subir documento <span aria-hidden="true">↑</span></button>
            </form>
        </section>
        <section class="admin-panel">
            <div class="panel-heading"><h2>Documentos publicados</h2><span class="count-pill"><?= count($documents) ?></span></div>
            <?php if ($documents === []): ?><p class="panel-empty">Todavía no hay documentos.</p><?php else: ?>
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Documento</th><th>Archivo</th><th>Fecha</th><th><span class="sr-only">Acciones</span></th></tr></thead><tbody>
                <?php foreach ($documents as $document): ?><tr><td class="table-title"><?= e($document['title']) ?></td><td><?= number_format((int) $document['file_size'] / 1048576, 1, ',', '.') ?> MB</td><td><?= e(format_date($document['created_at'])) ?></td><td><form method="post" action="documents.php" data-confirm="¿Eliminar este documento PDF? Esta acción no se puede deshacer."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $document['id'] ?>"><button type="submit" class="text-button text-button--danger">Eliminar</button></form></td></tr><?php endforeach; ?>
            </tbody></table></div><?php endif; ?>
        </section>
    </div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
