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
