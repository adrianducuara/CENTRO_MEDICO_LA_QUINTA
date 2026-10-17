<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Centro Médico La Quinta en Ibagué | Nuestra institución';
$pageDescription = 'Conozca la identidad institucional, misión, visión y compromiso de calidad de Centro Médico La Quinta en Ibagué.';
$canonicalPath = 'nosotros.php';
$activePage = 'about';
require __DIR__ . '/includes/header.php';
?>
<section class="page-hero page-hero--about">
    <div class="container">
        <p class="eyebrow eyebrow--light">Centro Médico La Quinta</p>
        <h1>CLÍNICA INTEGRAL PROVIDA S.A.S.</h1>
        <p>Identidad institucional, compromiso con la calidad y atención centrada en nuestros usuarios.</p>
    </div>
</section>

<section class="section about-intro">
    <div class="container about-intro-grid">
        <div class="about-photo"><img src="public/assets/images/fachada-clinica.jpeg" alt="Instalaciones del Centro Médico La Quinta" width="1365" height="768" loading="lazy" decoding="async"></div>
        <div class="about-copy">
            <p class="eyebrow">Nuestra institución</p>
            <h2>Misión, visión y política de calidad</h2>
            <p>Estos pilares orientan la gestión y la atención de CLÍNICA INTEGRAL PROVIDA S.A.S.</p>
            <a class="button button--blue" href="#pilares">Conoce nuestros pilares <span aria-hidden="true">↓</span></a>
        </div>
    </div>
</section>

<section class="section values-section" id="pilares">
    <div class="container">
        <div class="section-heading section-heading--center">
            <p class="eyebrow">Pilares institucionales</p>
            <h2>El compromiso que orienta nuestra atención</h2>
        </div>
        <div class="values-grid">
            <article class="value-card value-card--mission"><span class="value-icon" aria-hidden="true">◎</span><h3>Misión</h3><p>Somos una Institución de servicios de salud de primer nivel de atención, brindando a los usuarios el acceso a los servicios de promoción, prevención, tratamiento y rehabilitación de la enfermedad, con un equipo humano comprometido con la calidad en la atención actuando con responsabilidad, ética, atención efectiva, oportuna y personalizada.</p></article>
            <article class="value-card value-card--vision"><span class="value-icon" aria-hidden="true">↗</span><h3>Visión</h3><p>CLINICA INTEGRAL PROVIDA S.A.S, será una Institución prestadora de servicios de salud del primer nivel de atención, prestando servicios con calidad, convirtiéndola en una Institución eficiente, competitiva y de reconocimiento en la sociedad Ibaguereña y Tolimense.</p></article>
            <article class="value-card value-card--quality"><span class="value-icon" aria-hidden="true">✓</span><h3>Política de Calidad</h3><p>La CLINICA INTEGRAL PROVIDA, se compromete a orientar su gestión a la obtención de beneficios y resultados de calidad para los usuarios, dentro de las políticas establecidas para tal por el Ministerio de la Protección Social y órganos competentes.</p></article>
        </div>
    </div>
</section>

<section class="about-callout">
    <div class="container about-callout-inner">
        <div><p class="eyebrow eyebrow--light">Estamos para ayudarte</p><h2>Tu salud merece atención clara y cercana.</h2></div>
        <a class="button button--light" href="servicios.php">Conoce nuestros servicios <span aria-hidden="true">→</span></a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
