<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/videos.php';

$statement = db()->query("SELECT id, title, content, image_path, created_at FROM posts WHERE status = 'published' ORDER BY created_at DESC, id DESC LIMIT 12");
$latestPosts = $statement->fetchAll();
$featuredServices = public_services();
$latestVideos = public_videos(12);
$pageTitle = 'Centro Médico La Quinta en Ibagué | Servicios y atención';
$pageDescription = 'Centro Médico La Quinta en Ibagué: servicios de atención, horarios, ubicación y canales para solicitar orientación sobre una cita.';
$canonicalPath = '';
$structuredData = [seo_medical_clinic_schema(), seo_website_schema()];
$activePage = 'home';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <p class="eyebrow eyebrow--light">Centro Médico La Quinta</p>
            <h1 class="typewriter-title" data-typewriter="Mantenemos la salud a su alcance." aria-label="Mantenemos la salud a su alcance."><span id="typewriter-text" aria-hidden="true">Mantenemos la salud a su alcance.</span><span class="typewriter-cursor" aria-hidden="true">|</span></h1>
            <p class="hero-lead">Atención cercana para consulta, apoyo diagnóstico y bienestar, con orientación clara desde el primer contacto.</p>
            <div class="hero-actions">
                <a class="button button--light" href="https://wa.me/573168312283?text=Hola%2C%20quisiera%20solicitar%20una%20cita." target="_blank" rel="noopener noreferrer">Solicitar una cita <span aria-hidden="true">↗</span></a>
                <a class="button button--outline-light" href="#atencion">Explorar servicios</a>
            </div>
            <div class="hero-facts">
                <div><strong>Horario</strong><span>Lun–vie: 6:30 a. m.–7:00 p. m.<br>Sáb: 7:00 a. m.–5:00 p. m.</span></div>
                <div><strong>Ubicación</strong><span>Cra. 5 #39-72<br>Ibagué, Tolima</span></div>
            </div>
        </div>
        <div class="hero-image">
            <img src="public/assets/images/fachada-clinica.jpeg" alt="Fachada de Centro Médico La Quinta" width="1365" height="768" fetchpriority="high">
            <div class="image-badge"><span class="badge-dot"></span> Cuidamos de ti y tu familia</div>
        </div>
    </div>
</section>

<?php if ($latestPosts !== [] || $latestVideos !== []): ?>
<section class="section section-news">
    <div class="container">
        <div class="section-heading">
            <div><p class="eyebrow">Novedades</p><h2>Noticias y actualidad</h2></div>
            <a class="text-link" href="posts.php">Ver todas las novedades <span aria-hidden="true">→</span></a>
        </div>
        <?php if ($latestPosts !== []): ?><div class="carousel" data-carousel="noticias" aria-label="Noticias y actualidad">
            <div class="carousel-wrapper carousel-viewport"><div class="carousel-track">
            <?php foreach ($latestPosts as $post): ?><div class="carousel-slide">
            <article class="news-card">
                <div class="news-card-art<?= !empty($post['image_path']) ? ' news-card-art--image' : '' ?>"<?= !empty($post['image_path']) ? '' : ' aria-hidden="true"' ?>><?php if (!empty($post['image_path'])): ?><img src="post-image.php?id=<?= (int) $post['id'] ?>" alt="Imagen de <?= e($post['title']) ?>"><?php else: ?><span>CMQ</span><i>+</i><?php endif; ?></div>
                <div class="news-card-body">
                    <p class="card-date"><?= e(format_date($post['created_at'])) ?> <span>·</span> Actualidad</p>
                    <h3><a href="post.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a></h3>
                    <p><?= e(post_excerpt($post['content'])) ?></p>
                    <a class="text-link" href="post.php?id=<?= (int) $post['id'] ?>">Leer más <span aria-hidden="true">→</span></a>
                </div>
            </article>
            </div><?php endforeach; ?>
            </div></div>
            <div class="carousel-controls"><button class="carousel-button" type="button" data-carousel-prev aria-label="Ver noticias anteriores"><span aria-hidden="true">‹</span></button><button class="carousel-button" type="button" data-carousel-next aria-label="Ver más noticias"><span aria-hidden="true">›</span></button></div>
        </div><?php endif; ?>
        <?php if ($latestVideos !== []): ?>
        <div class="public-video-heading"><div><p class="eyebrow">Contenido multimedia</p><h2>Videos recientes</h2></div></div>
        <div class="carousel" data-carousel="videos" aria-label="Videos recientes"><div class="carousel-wrapper carousel-viewport"><div class="carousel-track">
            <?php foreach ($latestVideos as $video): ?><div class="carousel-slide"><article class="video-card video-card--public"><div class="video-card-preview"><?php render_video_preview($video); ?></div><div class="video-card-body"><p class="card-date"><?= e(format_date($video['created_at'])) ?> <span>·</span> Video</p><h3><?= e($video['title']) ?></h3></div></article></div>
            <?php endforeach; ?>
        </div></div><div class="carousel-controls"><button class="carousel-button" type="button" data-carousel-prev aria-label="Ver videos anteriores"><span aria-hidden="true">‹</span></button><button class="carousel-button" type="button" data-carousel-next aria-label="Ver más videos"><span aria-hidden="true">›</span></button></div></div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="mpp-section" id="mi-paciente-preferencial" aria-labelledby="mpp-title">
    <div class="container mpp-container">
        <h2 class="sr-only" id="mpp-title">Tarjeta Mi Paciente Preferencial</h2>
        <div class="mpp-images">
            <picture>
                <source srcset="public/assets/images/mi-paciente-preferencial-tarjeta.webp" type="image/webp">
                <img class="mpp-image" src="public/assets/images/mi-paciente-preferencial-tarjeta.png" alt="Tarjeta Mi Paciente Preferencial de Centro Médico La Quinta, con una familia, vigencia de un año y descuentos exclusivos para sus servicios." width="1672" height="941" loading="lazy" decoding="async">
            </picture>
            <picture>
                <source srcset="public/assets/images/mi-paciente-preferencial-beneficios.webp" type="image/webp">
                <img class="mpp-image" src="public/assets/images/mi-paciente-preferencial-beneficios.png" alt="Beneficios de la tarjeta Mi Paciente Preferencial: 5 por ciento en droguería, ecografías y RX; 10 por ciento con especialistas; 15 por ciento con médico general; y de 30 hasta 50 por ciento en procedimientos odontológicos seleccionados." width="1672" height="941" loading="lazy" decoding="async">
            </picture>
        </div>
    </div>
</section>

<section class="section pathways" id="atencion">
    <div class="container">
        <div class="section-heading section-heading--center">
            <p class="eyebrow">Atención integral</p>
            <h2>Elige cómo podemos acompañarte</h2>
            <p>Estamos aquí para ayudarte a dar el siguiente paso hacia tu bienestar.</p>
        </div>
        <div class="carousel" data-carousel="servicios" aria-label="Servicios de atención"><div class="carousel-wrapper carousel-viewport"><div class="carousel-track">
            <?php foreach ($featuredServices as $index => $service): ?><div class="carousel-slide">
            <article class="pathway-card <?= $index === 2 ? 'pathway-card--green' : '' ?>">
                <span class="pathway-number"><?= e($service['icon']) ?></span>
                <h3><?= e($service['title']) ?></h3>
                <p><?= e($service['description']) ?></p>
                <a href="servicios.php">Conocer más <span aria-hidden="true">→</span></a>
            </article>
            </div><?php endforeach; ?>
        </div></div><div class="carousel-controls"><button class="carousel-button" type="button" data-carousel-prev aria-label="Ver servicios anteriores"><span aria-hidden="true">‹</span></button><button class="carousel-button" type="button" data-carousel-next aria-label="Ver más servicios"><span aria-hidden="true">›</span></button></div></div>
        <p class="section-cta"><a class="button button--blue" href="servicios.php">Ver todos los servicios <span aria-hidden="true">→</span></a></p>
    </div>
</section>

<section class="location">
    <div class="container location-grid">
        <div><p class="eyebrow eyebrow--light">Estamos cerca de ti</p><h2>Visítanos en Ibagué</h2><p>Cra. 5 #39-72, Ibagué, Tolima, Colombia</p><a class="button button--light" href="https://maps.app.goo.gl/wHtHKyfoqDu1UJVv9" target="_blank" rel="noopener noreferrer">Cómo llegar <span aria-hidden="true">↗</span></a></div>
        <div class="map-card">
            <iframe title="Mapa de ubicación del Centro Médico La Quinta en Ibagué" src="https://www.google.com/maps?q=4.436151,-75.213724&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <span class="map-label"><span aria-hidden="true">⌖</span> Centro Médico La Quinta</span>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
