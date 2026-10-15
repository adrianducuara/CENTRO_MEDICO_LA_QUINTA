-- Importa este archivo después de seleccionar tu base de datos en phpMyAdmin.
-- No intenta crear ni seleccionar una base de datos, para funcionar en hosting compartido.

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(80) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email_verified_at DATETIME NULL,
  session_version INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_username (username),
  UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  content TEXT NOT NULL,
  image_path VARCHAR(255) NULL,
  image_mime VARCHAR(80) NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_posts_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS documentos_pdf (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name CHAR(36) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_documentos_pdf_stored_name (stored_name),
  KEY idx_documentos_pdf_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS servicios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(120) NOT NULL,
  description TEXT NOT NULL,
  icon VARCHAR(12) NOT NULL DEFAULT '✚',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'published',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_servicios_status_order (status, sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pqrsf (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  reference_code CHAR(27) NOT NULL,
  request_type ENUM('petition', 'complaint', 'claim', 'suggestion', 'compliment') NOT NULL,
  document_type ENUM('CC', 'CE', 'TI', 'PA', 'OT') NOT NULL,
  document_number VARCHAR(30) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(254) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  data_accepted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('received', 'in_progress', 'closed') NOT NULL DEFAULT 'received',
  notification_status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
  notification_attempted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_pqrsf_reference_code (reference_code),
  KEY idx_pqrsf_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  source_type ENUM('local', 'external') NOT NULL,
  source_value VARCHAR(255) NOT NULL,
  mime_type VARCHAR(80) NULL,
  file_size BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_videos_source_value (source_value),
  KEY idx_videos_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_oauth_tokens (
  id TINYINT UNSIGNED NOT NULL,
  client_id VARCHAR(255) NOT NULL,
  client_secret_encrypted TEXT NOT NULL,
  access_token_encrypted TEXT NULL,
  refresh_token_encrypted TEXT NULL,
  token_expires_at DATETIME NULL,
  sender_email VARCHAR(254) NOT NULL DEFAULT 'direccionadm.cmq@gmail.com',
  recipient_email VARCHAR(254) NOT NULL DEFAULT 'direccionadm.cmq@gmail.com',
  authorized_email VARCHAR(254) NULL,
  connection_status VARCHAR(32) NOT NULL DEFAULT 'pending',
  last_error VARCHAR(255) NULL,
  last_checked_at DATETIME NULL,
  last_verified_at DATETIME NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO servicios (title, description, icon, sort_order, status)
SELECT 'Consulta Médica General', 'Valoración médica integral orientada al diagnóstico, tratamiento oportuno y prevención de enfermedades de primer nivel, promoviendo la salud continua del paciente y su familia.', '🩺', 10, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Odontología', 'Atención en salud oral preventiva y correctiva, ofreciendo limpieza, restauración, profilaxis y cuidado integral para mantener una sonrisa sana.', '🦷', 20, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Ecografía', 'Estudio de diagnóstico por imagen por ultrasonido de alta precisión para la evaluación detallada de órganos internos, tejidos blandos y seguimiento gestacional.', '〰', 30, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Radiografía', 'Servicio de radiología digital eficiente para la toma e interpretación de imágenes diagnósticas óseas y torácicas, garantizando resultados oportunos.', '☢', 40, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Dermatología', 'Diagnóstico, tratamiento y cuidado especializado para afecciones de la piel, cabello y uñas, orientado a la salud dermatológica y estética clínica.', '✦', 50, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Nutrición', 'Evaluación antropométrica y elaboración de planes de alimentación personalizados para el control de peso, manejo de condiciones metabólicas y hábitos de vida saludable.', '🍃', 60, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Ortopedia', 'Valoración especializada del sistema musculoesquelético para la prevención, diagnóstico y manejo de lesiones en articulaciones, huesos y ligamentos.', '🦴', 70, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Psicología', 'Acompañamiento profesional y terapia psicológica enfocada en la salud mental, gestión emocional y bienestar psicológico individual o familiar.', '♡', 80, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Gastroenterología', 'Atención especializada en la prevención, diagnóstico y tratamiento de trastornos y enfermedades del sistema digestivo, estómago e intestinos.', '◉', 90, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Terapias Respiratorias', 'Tratamiento integral para la rehabilitación y manejo de patologías pulmonares y respiratorias agudas o crónicas en adultos y niños.', '≋', 100, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Cardiología', 'Evaluación y control preventivo y terapéutico de la salud cardiovascular, arritmias e hipertensión arterial para un corazón sano.', '♥', 110, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios)
UNION ALL
SELECT 'Nebulización e Inyectología', 'Procedimientos ambulatorios asistidos por personal de enfermería calificado para la administración segura de medicamentos inhalados e inyectables bajo prescripción médica.', '✚', 120, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios);

INSERT INTO servicios (title, description, icon, sort_order, status)
SELECT 'Droguería', 'Suministro y dispensación oportuna de medicamentos éticos, genéricos y material de insumos médicos de primera necesidad, garantizando estándares de calidad, seguridad y asesoría farmacéutica para el paciente.', '💊', 130, 'published'
WHERE NOT EXISTS (SELECT 1 FROM servicios WHERE title = 'Droguería');
