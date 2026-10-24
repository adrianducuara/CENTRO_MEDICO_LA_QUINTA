# Centro Médico La Quinta

## Contraseña y recuperación de la cuenta

La política compartida exige entre 8 y 16 puntos de código Unicode, confirmación idéntica, y rechaza contraseñas comunes de `config/password-blocklist.json`, secuencias simples, repeticiones y datos de la cuenta. Se aplica a creación por CLI, cambios desde el panel y recuperación. No exige combinaciones de mayúsculas, números o símbolos. El esquema actual no guarda nombres completos de administradores; el validador también comprueba `full_name`/`name` si se incorporan a la cuenta. La lista local es ampliable y no constituye una comprobación exhaustiva de contraseñas filtradas.

Las nuevas contraseñas usan Argon2id (19 MiB, 2 iteraciones, 1 hilo); el servidor debe soportarlo. Las contraseñas existentes siguen verificándose con su hash actual. No hay truncamiento a 72 bytes ni recorte de espacios. La longitud se cuenta en puntos de código; se conservan los bytes UTF-8 exactos, sin normalización Unicode. La validación en vivo se ejecuta localmente en el navegador y el servidor vuelve a validar todo. En recuperación, los datos personales se comprueban al enviar, sin exponerlos en la página pública.

La longitud mínima de 8 responde al requisito del proyecto: NIST SP 800-63B-4 exige 15 para autenticación de un solo factor. Las restricciones adicionales de patrones son requisitos del proyecto; no se afirma cumplimiento íntegro de NIST. Referencias: https://pages.nist.gov/800-63-4/sp800-63b.html y https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html.

Después de instalar o actualizar, ejecuta `php scripts/migrate-account-security.php` una vez antes de servir el sitio. La migración es idempotente y conserva las cuentas y contraseñas existentes. Las sesiones anteriores deberán iniciar sesión nuevamente.

En **Mi cuenta / Seguridad**, el administrador puede cambiar su contraseña proporcionando la actual, y guardar/verificar su correo de recuperación. La dirección existente se conserva hasta confirmar la nueva. El cambio de contraseña o la confirmación del correo cierra todas las sesiones e invalida los enlaces pendientes.

Para habilitar **Olvidé mi contraseña**, conecta y prueba la cuenta en **Google / Gmail**, y verifica el correo desde **Mi cuenta / Seguridad**. Los correos de recuperación van a la dirección del administrador, sin cambiar el destinatario de PQRSF. No se considera verificado automáticamente ningún correo existente.

Configura `APP_URL` en el entorno del servidor con la URL HTTPS del sitio (incluye el subdirectorio si corresponde). Solo localhost y 127.0.0.1 admiten HTTP para desarrollo. No se carga `.env` automáticamente. Los enlaces vencen en 30 minutos, se almacenan como hashes y solo pueden usarse una vez. Las solicitudes tienen límites por IP y correo; la respuesta pública no revela si existe la cuenta. La confirmación requiere pulsar un botón para evitar que los escáneres de correo consuman el enlace.

Pruebas: `php scripts/test-account-security.php` usa tablas temporales de MySQL en su propia conexión; no modifica cuentas reales ni envía correos.

Con el servidor local en `127.0.0.1:8000`, `php scripts/test-account-security-http.php` prueba los formularios con una cuenta desechable que elimina al terminar. No envía correos ni cambia la cuenta administradora.

Sitio público y panel de administración en PHP 8+, con MySQL/MariaDB y PDO. Incluye portada institucional, páginas de Nosotros, Servicios y PQRSF, novedades, documentos PDF, galería de videos y herramientas de administración protegidas por sesión y token CSRF.

## Requisitos

- PHP 8.1 o posterior con las extensiones `PDO`, `pdo_mysql` y `fileinfo`.
- MySQL 8.0+ o MariaDB compatible.
- Servidor web Apache/Nginx con PHP. En producción, apunta el document root a este directorio y desactiva la exposición de errores PHP.

## Instalación

1. Crea las tablas con `mysql -u root -p < schema.sql` (ajusta el usuario de base de datos si corresponde).
2. Configura las variables de entorno `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`.
3. Asegúrate de que PHP pueda crear y escribir en el directorio hermano `cmq-private-storage/pdfs` (se crea al subir el primer PDF). Los archivos se almacenan fuera del proyecto y se sirven mediante `download.php`. Si defines `PDF_STORAGE_PATH`, debe apuntar a una ruta absoluta que también esté fuera del document root.
4. Crea el primer administrador desde una terminal. En PowerShell:

   ```powershell
   $secure = Read-Host -AsSecureString "Contraseña (entre 8 y 16 caracteres)"
   $env:ADMIN_PASSWORD = [System.Net.NetworkCredential]::new("", $secure).Password
   php scripts/create-admin.php admin admin@example.com
   Remove-Item Env:ADMIN_PASSWORD
   $secure.Dispose()
   ```

   El comando solo crea la cuenta inicial cuando la tabla `usuarios` está vacía. La contraseña se almacena con `password_hash()`.
5. Abre `/admin/login.php` para administrar servicios, publicaciones, documentos, PQRSF y videos.

Si ya tienes una instalación con las tablas anteriores, ejecuta `php scripts/migrate-services.php` una vez después de configurar la conexión. El script crea `servicios` y registra los tres servicios iniciales solo cuando la tabla está vacía.

Para añadir PQRSF a una instalación existente, ejecuta `php scripts/migrate-pqrsf.php`. La notificación usa Gmail API cuando se configuran `GMAIL_OAUTH_CLIENT_ID`, `GMAIL_OAUTH_CLIENT_SECRET` y `GMAIL_OAUTH_REFRESH_TOKEN`; se envía a `direccionadm.cmq@gmail.com` y utiliza OAuth2 para obtener el token de acceso. Una API key de Google no autoriza el envío de correos con Gmail API. Si OAuth no está configurado, se usa SMTP con PHPMailer, incluido en `vendor/`. Configura las variables indicadas en `.env.example` en el entorno de producción. Para Gmail SMTP se requiere una contraseña de aplicación en `SMTP_PASSWORD`; no uses la contraseña normal de la cuenta. No subas un archivo `.env` con credenciales al repositorio.

Para añadir la galería de videos a una instalación existente, ejecuta `php scripts/migrate-videos.php`. Los archivos locales admiten MP4, WebM y OGG de hasta 100 MB, aunque el servidor debe tener `upload_max_filesize` y `post_max_size` configurados por encima de ese límite. El formulario admite una descripción detallada para la vista pública. Puedes definir `VIDEO_STORAGE_PATH` para usar un almacenamiento externo al document root.

Para habilitar imágenes destacadas en una instalación existente, ejecuta `php scripts/migrate-post-images.php`. Se admiten JPG, JPEG, PNG, WEBP, GIF y SVG de hasta 10 MB. Las imágenes se almacenan fuera del document root y se sirven mediante `post-image.php`; puedes definir `POST_IMAGE_STORAGE_PATH` para establecer otra ruta absoluta. Los SVG se limpian antes de guardarse.

Para desarrollo local, puedes iniciar el servidor integrado desde la raíz del proyecto con `php -S 127.0.0.1:8000` y visitar `http://127.0.0.1:8000`.

## Seguridad y contenido

- Las consultas utilizan sentencias preparadas PDO; el contenido textual se escapa al renderizarse.
- Los PDFs se validan por extensión, MIME, firma y tamaño (máximo 40 MB), reciben un nombre aleatorio y se guardan fuera de las rutas públicas normales. El archivo `.user.ini` incluye los límites de PHP requeridos; en servidores con PHP-FPM o una configuración global administrada, replica esos valores en la configuración activa del servidor.
- Las sesiones usan cookies `HttpOnly`, `SameSite=Strict` y `Secure` cuando el sitio se sirve por HTTPS. Los formularios administrativos validan CSRF.
- Las publicaciones y servicios usan texto plano; los borradores no se muestran en el sitio público.
- Las imágenes de publicaciones se validan por extensión, MIME, tamaño y estructura de imagen. Los archivos reciben nombres aleatorios y se sirven desde una ruta controlada.
- La página pública de Servicios toma su contenido de la tabla `servicios`. El panel permite establecer un icono breve, el orden y el estado de publicación de cada tarjeta.
- Las PQRSF se validan en el servidor, se guardan con un radicado único y se consultan solo desde el panel de administración. La notificación SMTP contiene el detalle solicitado y solo se dirige al correo institucional configurado.


## OAuth de Google administrado desde el panel

En una instalación existente ejecuta `php scripts/migrate-google-oauth.php`. Después entra a **Administración → Google / Gmail**, guarda el Client ID y Client Secret de una credencial OAuth de tipo **Aplicación web** y copia exactamente la URI mostrada allí en Google Cloud Console → Credenciales → URI de redirección autorizados. El panel cifra el Client Secret y los tokens de acceso y renovación; el token se renueva automáticamente cuando llega una PQRSF.

En producción define `APP_ENCRYPTION_KEY` como una clave Base64 de 32 bytes, fuera del repositorio. Si la URL pública no puede detectarse de forma automática, define `GMAIL_OAUTH_REDIRECT_URI` con la misma URI registrada en Google Cloud.

### Hosting compartido sin variables de entorno

Si el panel no permite definir variables de entorno para PHP, copia `config/runtime.local.example.php` como `config/runtime.local.php` en el servidor y completa `app_url` y `gmail_oauth_redirect_uri` con la URL HTTPS pública. Este archivo no se versiona. La URI debe terminar exactamente en `/admin/google-oauth-callback.php` y debe coincidir con la registrada en Google Cloud.

La entrega actual usa exclusivamente OAuth 2.0 y Gmail API con google/apiclient; las notificaciones PQRSF no usan SMTP como alternativa. La cuenta solo se muestra como conectada después de una prueba de envío satisfactoria.

## SEO técnico e indexación

### Implementación — 16 de octubre de 2026

Se añadió una base de SEO técnico orientada a un sitio médico local. Los cambios
no crean contenido clínico, perfiles de profesionales, reseñas ni afirmaciones
no verificadas.

#### Hallazgos iniciales

- No existían un `robots.txt` público, sitemap, etiquetas canonical,
  metadescripciones o marcado estructurado compartido.
- Las páginas públicas compartían una plantilla sin controles SEO por página.
- El enlace de acceso administrativo estaba expuesto desde el footer público.
- Las rutas de administración, procesamiento de PQRSF y archivos protegidos no
  declaraban un control de indexación común.

#### Archivos creados

- `includes/seo.php`: origen canónico validado desde `APP_URL`, utilidades de
  URL, controles de indexación y Schema JSON-LD.
- `robots.php`: genera el contenido de `robots.txt` con la URL de sitemap
  obtenida de la configuración canónica.
- `sitemap.php`: sitemap XML dinámico de páginas públicas y publicaciones
  publicadas; usa `updated_at` para `lastmod` y excluye contenido privado.
- `.htaccess`: publica `robots.php` como `/robots.txt`, `sitemap.php` como
  `/sitemap.xml` y establece el documento 404 en Apache.
- `404.php`: respuesta 404 útil y no indexable.
- `scripts/test-seo.php`: pruebas unitarias de la configuración y Schema SEO.
- `scripts/test-public-session.php`: comprueba que una respuesta pública no
  cree sesión y que los formularios protegidos sí puedan iniciar CSRF.
- `includes/indexnow.php`: construye y envía notificaciones IndexNow con
  validación del host canónico y límites del protocolo.
- `7a9e7d560ea5da946d44edf4c1a20370.txt`: archivo público de validación de la
  clave IndexNow en la raíz web.
- `scripts/test-indexnow.php` y `scripts/submit-indexnow.php`: prueba local y
  envío inicial por CLI, respectivamente.

#### Cambios aplicados

- Se añadió `X-Robots-Tag: noindex, nofollow, noarchive` a administración,
  procesamiento de PQRSF y archivos privados/servidos por endpoint.
- Se eliminó el enlace público al login administrativo del footer.
- Se implementaron title, metadescripción, canonical y directiva robots por
  página pública. La PQRSF y páginas 404 son `noindex,follow`.
- Se añadió marcado `MedicalClinic`/`LocalBusiness` y `WebSite` en inicio,
  tomando únicamente nombre, razón social, dirección, teléfono, coordenadas y
  horarios que ya se mostraban en el sitio. No se incluyen reseñas,
  calificaciones ni especialidades no verificadas.
- Las publicaciones publicadas emiten Schema `Article` con fechas de creación
  y actualización. No se atribuye autor o revisor médico hasta contar con esos
  datos verificables.
- Se declararon las dimensiones verificadas del logotipo y de la fotografía de
  fachada; la imagen principal recibe prioridad de carga y la imagen secundaria
  se carga de forma diferida para reducir cambios de diseño acumulados.
- Se corrigió la URL raíz de `MedicalClinic` y `WebSite` para evitar barras
  finales duplicadas en el JSON-LD.
- Se añadieron etiquetas Open Graph y Twitter Card con una imagen institucional
  HTTPS para que las páginas públicas tengan vistas previas completas al
  compartirse. Las publicaciones con imagen usan su propia imagen; no se
  declaran dimensiones ni tipo MIME cuando el archivo subido no lo garantiza.
- Se eliminó el inicio incondicional de sesión PHP en páginas públicas. Ahora
  solo administración y PQRSF inician sesión: las páginas indexables, el
  sitemap y los recursos públicos dejan de crear una cookie PHP o de forzar
  `Cache-Control: no-store`, lo que permite que el CDN las almacene según su
  configuración.
- Se integró IndexNow tras la verificación del sitemap en Bing Webmaster Tools.
  Las publicaciones notifican su URL, inicio y novedades al crear, modificar,
  despublicar o eliminar; los servicios notifican inicio y servicios; los
  documentos notifican su listado; y los videos notifican inicio y novedades.
  La notificación se realiza después de guardar y nunca revierte el cambio de
  contenido si el proveedor no responde. Cada intento tiene un límite de tres
  segundos y los fallos quedan únicamente en el registro PHP. Si IndexNow
  responde `429`, se guarda un límite privado de 24 horas para impedir envíos
  repetidos desde cambios posteriores de contenido.

### Configuración obligatoria

Defina `APP_URL` en el entorno de producción con la URL canónica HTTPS, sin
barra final. En hosting compartido sin variables de entorno, cree
`config/runtime.local.php` a partir de `config/runtime.local.example.php`:

```php
return [
    'app_url' => 'https://www.su-dominio-real.co',
    'gmail_oauth_redirect_uri' => 'https://www.su-dominio-real.co/admin/google-oauth-callback.php',
];
```

No suba este archivo al repositorio. Sin `APP_URL`, el sitio continúa
funcionando, pero por seguridad no emitirá canonical ni sitemap con un dominio
inventado; `/sitemap.xml` responderá 503 hasta que se configure.

### Verificación después del despliegue

Con el dominio real configurado, compruebe:

```bash
curl -I https://www.su-dominio-real.co/
curl https://www.su-dominio-real.co/robots.txt
curl https://www.su-dominio-real.co/sitemap.xml
```

La home debe devolver `200`; `robots.txt` debe referenciar un sitemap HTTPS
absoluto; el sitemap debe devolver XML, incluir solo URLs públicas canónicas y
no contener `/admin/`, `process-pqrsf.php`, `download.php`, `video.php` ni
`post-image.php`.

Ejecute las pruebas locales:

```bash
php scripts/test-seo.php
php scripts/test-public-session.php
php scripts/test-indexnow.php
find . -path ./vendor -prune -o -name '*.php' -type f -print0 | xargs -0 -n1 php -l
php scripts/test-account-security.php
```

Para una prueba HTTP local, inicie el servidor con una URL temporal segura:

```bash
APP_URL=https://centromedicolquinta.test php -S 127.0.0.1:8000
```

El servidor interno de PHP no interpreta `.htaccess`; pruebe directamente
`/robots.php` y `/sitemap.php` en local. En Apache, verifique las rutas finales
`/robots.txt` y `/sitemap.xml` después del despliegue.

### IndexNow

La clave de IndexNow se valida mediante el archivo público
`/7a9e7d560ea5da946d44edf4c1a20370.txt`, que debe responder `200`, con una
única línea que coincide exactamente con el nombre sin la extensión `.txt`.
No lo bloquee con reglas de seguridad ni lo cambie sin actualizar
`INDEXNOW_KEY_FILE` en `includes/indexnow.php`.

Después de desplegar los archivos, ejecute una sola vez desde la raíz del
proyecto en el hosting:

```bash
php scripts/test-indexnow.php
php scripts/submit-indexnow.php
curl -i https://cmqclinicacentromedicolaquinta.com/7a9e7d560ea5da946d44edf4c1a20370.txt
```

El primer comando no realiza conexiones externas. El segundo notifica las URLs
públicas actuales y solo se puede ejecutar por CLI; no es accesible por web
porque `scripts/` está protegido. Una respuesta `200` o `202` de IndexNow
significa recepción o validación pendiente, no indexación garantizada. IndexNow
complementa el sitemap para motores participantes; no sustituye Search Console
ni el rastreo de Google.

Si el envío devuelve `429`, no genere otra clave ni repita el comando. El
proveedor aplicó un límite de frecuencia; espere al menos 24 horas y ejecute
`php scripts/submit-indexnow.php` una sola vez. La aplicación registra ese
límite de forma privada para que los cambios administrativos no vuelvan a
enviar solicitudes durante ese periodo.

#### Pruebas ejecutadas en esta intervención

- `node scripts/test-password-policy-js.cjs`: superada (14 comprobaciones).
- `git diff --check`: superada, sin errores de espacios.
- Revisión estática de títulos/H1, rutas de noindex y referencias a datos
  estructurados: sin hallazgos críticos.
- No fue posible ejecutar `php -l`, `scripts/test-seo.php` ni las pruebas PHP
  existentes porque este entorno de trabajo no tiene un binario PHP instalado
  ni disponible en rutas estándar. Es obligatorio ejecutarlas en el servidor
  de despliegue o en una estación con PHP 8.1+ antes de publicar.
- La comprobación HTTPS pública previa al ajuste confirmó que la página de
  inicio respondía `200`, pero enviaba `PHPSESSID` y
  `Cache-Control: no-store` a visitantes anónimos. El cambio de sesiones debe
  comprobarse tras desplegar: la home y `sitemap.xml` no deben incluir
  `Set-Cookie: PHPSESSID` ni `no-store`; PQRSF y administración deben conservar
  sus sesiones, CSRF, avisos y autenticación.
- Rastreo público de solo lectura (17 de octubre de 2026): las cinco URLs
  declaradas en el sitemap respondieron `200`; HTTP y `www` redirigieron con
  `301` a la URL HTTPS canónica; una ruta inexistente devolvió `404` con
  `X-Robots-Tag: noindex, nofollow, noarchive`. Las páginas revisadas tienen
  título, metadescripción, canonical y directiva robots coherentes. La consulta
  a la API pública de PageSpeed no devolvió categorías Lighthouse, por lo que
  no se registraron métricas ni se aplicaron cambios de rendimiento basados en
  esa respuesta incompleta.

### Seguridad de hosting y protección de código fuente

La raíz incluye `.htaccess` para Apache/LiteSpeed. Además de las rutas SEO y
la página 404, desactiva el listado de directorios y devuelve `403` a
solicitudes HTTP de `config/`, `includes/`, `scripts/` y `vendor/`. También
deniega archivos de configuración y mantenimiento (`composer.json`,
`composer.lock`, `.sql`, `.md`, `.ini`, `.env`, `.log`, `.phps` y extensiones
de respaldo o archivo), así como ficheros/directorios ocultos como `.git`.
`.well-known/` queda excluido para permitir desafíos de certificados y
verificaciones autorizadas.

Las reglas no bloquean las páginas PHP públicas, `admin/`, `public/assets/`,
CSS, JavaScript, imágenes, `/robots.txt` ni `/sitemap.xml`. Bloquear un
directorio mediante rewrite solo afecta solicitudes web externas; PHP aún
puede cargar de forma interna sus archivos desde `includes/` y `vendor/`.

Cuando `mod_headers` está disponible, se añaden a respuestas estáticas y
dinámicas: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`,
`Referrer-Policy: strict-origin-when-cross-origin` y una
`Permissions-Policy` que desactiva cámara, geolocalización, micrófono, pagos
y USB. No se ha desplegado CSP todavía: el sitio usa Google Maps, WhatsApp,
YouTube/Vimeo y OAuth, por lo que una política sin pruebas en producción puede
interrumpir funciones legítimas.

#### Verificación de seguridad posterior al despliegue

Sustituya el dominio y compruebe que las rutas protegidas devuelvan `403` o
`404`, mientras que el sitio y las rutas SEO devuelvan `200`:

```bash
curl -I https://www.su-dominio-real.co/config/database.php
curl -I https://www.su-dominio-real.co/includes/bootstrap.php
curl -I https://www.su-dominio-real.co/vendor/autoload.php
curl -I https://www.su-dominio-real.co/schema.sql
curl -I https://www.su-dominio-real.co/README.md
curl -I https://www.su-dominio-real.co/.git/config
curl -I https://www.su-dominio-real.co/robots.txt
curl -I https://www.su-dominio-real.co/sitemap.xml
curl -I https://www.su-dominio-real.co/
```

No fue posible ejecutar estas pruebas HTTP ni validar Apache en este entorno,
porque no dispone del dominio de producción ni de un binario Apache local. No
se deben considerar las reglas activas hasta verificar estos resultados tras
el despliegue.

#### Equivalente para Nginx

Nginx no interpreta `.htaccess`. El administrador del servidor debe añadir
reglas equivalentes dentro del bloque `server` del dominio, antes de las reglas
generales de PHP, y validar con `nginx -t` antes de recargarlo:

```nginx
location ^~ /.well-known/ { allow all; }
location ~ ^/(?:config|includes|scripts|vendor)(?:/|$) { return 403; }
location ~* ^/(?:composer\.(?:json|lock)|phpunit\.xml(?:\.dist)?)$ { return 403; }
location ~* \.(?:sql|md|ini|env|log|phps|bak|old|orig|save|swp|zip|tar|gz|tgz|bz2|7z)$ { return 403; }
location ~ /\. { return 403; }

location = /robots.txt { try_files /robots.php =404; }
location = /sitemap.xml { try_files /sitemap.php =404; }
error_page 404 /404.php;
```

Las cabeceras equivalentes deben configurarse con `add_header ... always` en
Nginx. Antes de habilitar CSP, revisar en producción las solicitudes del
navegador y permitir únicamente los orígenes externos realmente necesarios.

### Herramientas externas

1. La propiedad de dominio se verificó en Google Search Console mediante DNS.
   Mantenga el registro TXT de verificación y revise periódicamente el informe
   de indexación y las inspecciones de URL.
2. Bing Webmaster Tools recibió el sitemap
   `https://cmqclinicacentromedicolaquinta.com/sitemap.xml` el 17 de octubre
   de 2026. Mientras figure con estado **En tratamiento**, no debe enviarse de
   nuevo: hay que esperar a que Bing termine de obtenerlo y procesarlo.
   Configure IndexNow solo después de que el sitemap muestre un resultado
   correcto y exista una clave válida con un flujo de publicación que notifique
   URLs nuevas o modificadas.
3. Valide el JSON-LD con Schema Markup Validator y Rich Results Test. El
   marcado ayuda a interpretar la entidad, pero no garantiza resultados
   enriquecidos.
4. Revise Search Console cada semana durante los primeros 60 días: indexación,
   errores de rastreo, acciones manuales, problemas de seguridad y rendimiento
   móvil.

#### Consolidación de marca local — pendiente de validación humana

La búsqueda pública del 17 de octubre de 2026 para variaciones de la marca en
Ibagué devolvió principalmente fichas de terceros. Algunas de ellas muestran
un dominio anterior (`clinicacentromedicolaquinta.com`), teléfonos u horarios
distintos de los publicados actualmente. No se deben copiar esos datos al
sitio: la dirección clínica debe definir una única fuente oficial de nombre
comercial, razón social, dirección, teléfono, horario, categoría y URL HTTPS.

Con esos datos aprobados, el responsable debe reclamar o corregir Google
Business Profile, Waze, directorios y redes institucionales. Usar exactamente
el mismo NAP (nombre, dirección y teléfono) y URL canónica
`https://cmqclinicacentromedicolaquinta.com/`; no añadir palabras clave al
nombre del perfil, ni reseñas o servicios no verificados. Esta consolidación
es prioritaria para consultas de marca como “clinica centro medico la quinta”.

### Limitaciones conocidas

#### Verificación de producción — 17 de octubre de 2026

Las comprobaciones públicas confirmaron que la home, `robots.txt` y
`sitemap.xml` responden `200`; el sitemap y las canonical usan
`https://cmqclinicacentromedicolaquinta.com`. Las rutas de configuración y
Git comprobadas responden `403`; el login administrativo devuelve
`X-Robots-Tag: noindex, nofollow, noarchive`, y una URL inexistente devuelve
`404` no indexable.

La comprobación también detectó que `http://` y `https://www.` respondían
`200`, lo que creaba variantes duplicadas. `.htaccess` incluye una redirección
permanente `301` al origen canónico sin `www`. Como StackCDN termina TLS antes
de Apache, la regla respeta `X-Forwarded-Proto: https`; de otro modo la URL
canónica HTTPS se redirigiría a sí misma. Después de cada cambio de esta regla
se debe comprobar HTTP, HTTPS, `www` y la URL canónica antes de enviar el
sitemap a buscadores.

Tras desplegar la corrección, una URL no almacenada en caché confirmó el
comportamiento esperado: HTTPS canónico devuelve `200`; HTTP y `www` devuelven
`301` al origen sin `www`. StackCDN aún entregaba una respuesta `301` antigua
para la raíz sin parámetros (`X-CDN-Cache-Status: HIT`). Antes de indexar, se
debe purgar la caché de StackCDN/StackCP para el dominio o, como mínimo, para
`/`. Luego repetir estas comprobaciones:

```bash
curl -I http://cmqclinicacentromedicolaquinta.com/
curl -I https://www.cmqclinicacentromedicolaquinta.com/
curl -I https://cmqclinicacentromedicolaquinta.com/
```

El resultado final debe ser `301`, `301` y `200`, respectivamente.

La propiedad de dominio `cmqclinicacentromedicolaquinta.com` fue verificada en
Google Search Console mediante DNS el 17 de octubre de 2026. Tras purgar la
caché CDN, la URL raíz y `/sitemap.xml` fueron comprobadas nuevamente con
respuesta `200`. Search Console informa que la URL está disponible para Google.
El sitemap también se envió a Bing Webmaster Tools y, al cierre de esta
actualización, figura como **En tratamiento**. Ese estado significa que Bing lo
recibió y lo está validando o rastreando; no es un error ni requiere reenviarlo.

- Las URLs de publicaciones conservan el formato `post.php?id=...`. Es una URL
  válida y canónica; no se introdujeron slugs para evitar una migración de base
  de datos y redirecciones sin una tabla de mapeo completa.
- El sitemap incluye documentos como página de listado, no URLs de descarga,
  porque las descargas se sirven desde almacenamiento protegido.
- No se añadieron Analytics, Tag Manager, consent banner ni píxeles: requieren
  identificadores, consentimiento y configuración externa válida. Nunca envíe
  datos de PQRSF o información clínica a analítica.

## Intervención humana requerida

En este orden de prioridad:

1. Esperar a que Bing Webmaster Tools cambie el estado del sitemap de **En
   tratamiento** a correcto o muestre un error accionable. No reenviar mientras
   se procesa.
2. Una vez Bing confirme el sitemap, configurar IndexNow con una clave generada
   y guardada fuera del repositorio, además de notificaciones desde las altas,
   actualizaciones y eliminaciones de contenido público.
3. Reclamar y verificar Google Business Profile. Mantener nombre, dirección,
   teléfono, horario y URL idénticos a los datos oficiales de la clínica.
4. Validar por dirección clínica y asesoría legal las políticas de privacidad,
   tratamiento de datos, aviso médico, servicios habilitados, profesionales,
   licencias y cualquier contenido médico antes de publicarlo.
5. Proporcionar perfiles y credenciales verificables de autores o revisores
   médicos antes de añadirlos al contenido o a Schema.
