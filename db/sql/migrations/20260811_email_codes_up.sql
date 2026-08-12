ALTER TABLE usuarios
ADD COLUMN email_verified_at DATETIME NULL;

UPDATE usuarios
SET email_verified_at = COALESCE(fecha_registro, NOW())
WHERE email_verified_at IS NULL;

CREATE TABLE email_codes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  purpose VARCHAR(32) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  used_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reset_token_hash CHAR(64) NULL,
  reset_token_expires_at DATETIME NULL,
  INDEX idx_email_codes_user_purpose (user_id, purpose),
  UNIQUE INDEX uq_email_codes_reset_token (reset_token_hash),
  CONSTRAINT fk_email_codes_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
