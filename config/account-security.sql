CREATE TABLE IF NOT EXISTS admin_security_tokens (
  token_hash CHAR(64) NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  purpose VARCHAR(16) NOT NULL,
  email VARCHAR(254) NOT NULL,
  session_version INT UNSIGNED NOT NULL,
  expires_at DATETIME NOT NULL,
  KEY idx_security_user (user_id, purpose),
  KEY idx_security_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admin_security_limits (
  bucket_hash CHAR(64) NOT NULL PRIMARY KEY,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  KEY idx_security_limit_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
