-- Migracion: verificacion de correo y OTP para registro/reseteo
-- Fecha: 2026-09-07

ALTER TABLE usuarios
  ADD COLUMN email_verificado TINYINT(1) NOT NULL DEFAULT 0 AFTER password,
  ADD COLUMN otp_secret_enc VARCHAR(255) NULL AFTER email_verificado,
  ADD COLUMN otp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER otp_secret_enc,
  ADD COLUMN otp_backup_codes_json TEXT NULL AFTER otp_enabled,
  ADD COLUMN updated_at DATETIME NULL AFTER fecha_registro;

CREATE TABLE pending_user_registrations (
  id INT(11) NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  apellidos VARCHAR(100) NOT NULL,
  email_enc VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  verification_code_hash VARCHAR(255) NOT NULL,
  code_expires_at DATETIME NOT NULL,
  attempts INT(11) NOT NULL DEFAULT 0,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_pending_email_enc (email_enc),
  KEY idx_pending_expires (code_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE password_reset_tokens (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  token_hash VARCHAR(255) NOT NULL,
  expires_at DATETIME NOT NULL,
  attempts INT(11) NOT NULL DEFAULT 0,
  consumed_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  PRIMARY KEY (id),
  KEY idx_prt_user (user_id),
  KEY idx_prt_expires (expires_at),
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
