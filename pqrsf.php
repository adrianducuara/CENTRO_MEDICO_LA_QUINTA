<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/pqrsf.php';
app_start_session();

$types = pqrsf_types();
$documentTypes = pqrsf_document_types();
$errors = [];
$values = [
    'request_type' => '', 'document_type' => '', 'document_number' => '', 'full_name' => '',
    'email' => '', 'phone' => '', 'subject' => '', 'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        http_response_code(400);
        exit('Solicitud no válida.');
    }
    foreach (array_keys($values) as $field) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $errors = validate_pqrsf_values($values, ($_POST['data_policy'] ?? '') === '1');

    if ($errors === []) {
        $reference = save_pqrsf($values);
        notify_pqrsf($reference, $values);
        $_SESSION['_pqrsf_reference'] = $reference;
        redirect('pqrsf.php');
    }
}

$reference = $_SESSION['_pqrsf_reference'] ?? null;
unset($_SESSION['_pqrsf_reference']);
$pageTitle = 'PQRSF | Centro Médico La Quinta';
$pageDescription = 'Canal de peticiones, quejas, reclamos, sugerencias y felicitaciones de Centro Médico La Quinta.';
$canonicalPath = 'pqrsf.php';
$robots = 'noindex,follow';
$activePage = 'pqrsf';
$extraScripts = ['public/assets/js/pqrsf-validation.js'];
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero--pqrsf"><div class="container"><p class="eyebrow eyebrow--light">Tu voz nos ayuda a mejorar</p><h1>Presenta tu PQRSF</h1><p>Registra una petición, queja, reclamo, sugerencia o felicitación. Te mostraremos un número de radicado al finalizar.</p></div></section>
<section class="section pqrsf-section"><div class="container pqrsf-layout">
    <aside class="pqrsf-aside"><p class="eyebrow">Canal de atención</p><h2>Te escuchamos</h2><p>Usa este formulario para comunicarte formalmente con nuestro equipo. Conserva el número de radicado para hacer seguimiento.</p><div class="pqrsf-help"><strong>Antes de enviar</strong><p>Evita incluir diagnósticos, contraseñas u otra información que no sea necesaria para atender tu solicitud.</p></div></aside>
    <div class="pqrsf-card">
        <?php if (is_string($reference)): ?>
            <div class="pqrsf-success" role="status"><span aria-hidden="true">✓</span><div><p class="eyebrow">Solicitud recibida</p><h2>Tu radicado es <?= e($reference) ?></h2><p>Guarda este código. Nuestro equipo revisará tu solicitud y se comunicará contigo por los datos registrados.</p></div></div>
        <?php else: ?>
            <h2>Datos de la solicitud</h2><p class="form-intro">Los campos marcados con <span aria-hidden="true">*</span> son obligatorios.</p>
            <?php if ($errors !== []): ?><div class="form-alert" role="alert">Revisa los campos señalados antes de enviar la solicitud.</div><?php endif; ?>
            <form id="pqrsf-form" method="post" action="pqrsf.php" data-endpoint="process-pqrsf.php" novalidate><?= csrf_field() ?><div class="honeypot" aria-hidden="true"><label for="website">Sitio web</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                <div class="public-form-grid">
                    <div class="form-field form-field--full"><label for="request_type">Tipo de solicitud <span aria-hidden="true">*</span></label><select id="request_type" name="request_type" required aria-invalid="<?= isset($errors['request_type']) ? 'true' : 'false' ?>"><option value="">Selecciona una opción</option><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>" <?= $values['request_type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?php if (isset($errors['request_type'])): ?><small><?= e($errors['request_type']) ?></small><?php endif; ?></div>
                    <div class="form-field"><label for="document_type">Tipo de documento <span aria-hidden="true">*</span></label><select id="document_type" name="document_type" required aria-invalid="<?= isset($errors['document_type']) ? 'true' : 'false' ?>"><option value="">Selecciona</option><?php foreach ($documentTypes as $key => $label): ?><option value="<?= e($key) ?>" <?= $values['document_type'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?php if (isset($errors['document_type'])): ?><small><?= e($errors['document_type']) ?></small><?php endif; ?></div>
                    <div class="form-field"><label for="document_number">Número de documento <span aria-hidden="true">*</span></label><input id="document_number" name="document_number" maxlength="30" value="<?= e($values['document_number']) ?>" required aria-invalid="<?= isset($errors['document_number']) ? 'true' : 'false' ?>"><?php if (isset($errors['document_number'])): ?><small><?= e($errors['document_number']) ?></small><?php endif; ?></div>
                    <div class="form-field form-field--full"><label for="full_name">Nombre completo <span aria-hidden="true">*</span></label><input id="full_name" name="full_name" maxlength="150" autocomplete="name" value="<?= e($values['full_name']) ?>" required aria-invalid="<?= isset($errors['full_name']) ? 'true' : 'false' ?>"><?php if (isset($errors['full_name'])): ?><small><?= e($errors['full_name']) ?></small><?php endif; ?></div>
                    <div class="form-field"><label for="email">Correo electrónico <span aria-hidden="true">*</span></label><input id="email" name="email" type="email" maxlength="254" autocomplete="email" value="<?= e($values['email']) ?>" required aria-invalid="<?= isset($errors['email']) ? 'true' : 'false' ?>"><?php if (isset($errors['email'])): ?><small><?= e($errors['email']) ?></small><?php endif; ?></div>
                    <div class="form-field"><label for="phone">Teléfono de contacto <span aria-hidden="true">*</span></label><input id="phone" name="phone" type="tel" maxlength="30" autocomplete="tel" value="<?= e($values['phone']) ?>" required aria-invalid="<?= isset($errors['phone']) ? 'true' : 'false' ?>"><?php if (isset($errors['phone'])): ?><small><?= e($errors['phone']) ?></small><?php endif; ?></div>
                    <div class="form-field form-field--full"><label for="subject">Asunto <span aria-hidden="true">*</span></label><input id="subject" name="subject" maxlength="180" value="<?= e($values['subject']) ?>" required aria-invalid="<?= isset($errors['subject']) ? 'true' : 'false' ?>"><?php if (isset($errors['subject'])): ?><small><?= e($errors['subject']) ?></small><?php endif; ?></div>
                    <div class="form-field form-field--full"><label for="message">Mensaje o detalle de la solicitud <span aria-hidden="true">*</span></label><textarea id="message" name="message" rows="8" maxlength="5000" required aria-invalid="<?= isset($errors['message']) ? 'true' : 'false' ?>"><?= e($values['message']) ?></textarea><?php if (isset($errors['message'])): ?><small><?= e($errors['message']) ?></small><?php endif; ?></div>
                </div>
                <label class="policy-check"><input id="data_policy" type="checkbox" name="data_policy" value="1" <?= ($_POST['data_policy'] ?? '') === '1' ? 'checked' : '' ?> required> <span>Acepto el tratamiento de mis datos personales para la gestión de esta PQRSF. <strong aria-hidden="true">*</strong></span></label>
                <button class="policy-link" type="button" data-policy-open aria-controls="policy-modal">Ver políticas de privacidad</button><?php if (isset($errors['data_policy'])): ?><small class="policy-error"><?= e($errors['data_policy']) ?></small><?php endif; ?>
                <div id="pqrsf-feedback" class="pqrsf-success" role="status" hidden></div>
                <button id="pqrsf-submit" class="button button--blue" type="submit" disabled>Enviar solicitud <span aria-hidden="true">→</span></button>
            </form>
            <div id="policy-modal" class="policy-modal" role="dialog" aria-modal="true" aria-labelledby="policy-modal-title" hidden><div class="policy-modal__backdrop" data-policy-close></div><div class="policy-modal__content" role="document"><button class="policy-modal__close" type="button" data-policy-close aria-label="Cerrar políticas de privacidad">×</button><p class="eyebrow">Tratamiento de datos</p><h2 id="policy-modal-title">Políticas de privacidad</h2><p>De conformidad con la Ley Estatutaria 1581 de 2012 de Protección de Datos Personales (Habeas Data), CLINICA INTEGRAL PROVIDA S.A.S. (Centro Médico La Quinta) informa que los datos suministrados en este formulario serán tratados de forma estricta, confidencial y segura. La información recolectada se utilizará exclusivamente para dar trámite, gestión y respuesta oportuna a su Petición, Queja, Reclamo, Sugerencia o Felicitación (PQRSF), así como para el seguimiento de la calidad en la atención médica prestada. El titular de los datos podrá ejercer sus derechos de conocer, actualizar, rectificar y solicitar la supresión de sus datos personales a través del canal oficial direccionadm.cmq@gmail.com.</p><button class="button button--blue" type="button" data-policy-accept>He leído y acepto las políticas</button></div></div>
        <?php endif; ?>
    </div>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
