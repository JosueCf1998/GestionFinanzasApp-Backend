USE u992910078_finanzas_db;

-- ============================================================
-- MIGRACION: INDEPENDIZAR ESTATUS DE EXPERIENCIA
-- Crea una tabla parametrizable para niveles y sus limites de XP.
-- ============================================================

CREATE TABLE IF NOT EXISTS learning_experience_statuses (
    id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    code VARCHAR(30) NOT NULL,
    name VARCHAR(80) NOT NULL,
    xp_limit INT UNSIGNED NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_learning_experience_statuses_code (code),
    UNIQUE KEY uk_learning_experience_statuses_name (name),
    KEY idx_learning_experience_statuses_status (status),
    KEY idx_learning_experience_statuses_limit (xp_limit)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO learning_experience_statuses (code, name, xp_limit, sort_order, status)
VALUES
    ('PRINCIPIANTE', 'Principiante', 299, 1, 1),
    ('INTERMEDIO', 'Intermedio', 999, 2, 1),
    ('AVANZADO', 'Avanzado', 2499, 3, 1),
    ('EXPERTO', 'Experto', 2999, 4, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    xp_limit = VALUES(xp_limit),
    sort_order = VALUES(sort_order),
    status = VALUES(status);
