-- ============================================================
-- MÓDULO DE PRESUPUESTOS
-- MySQL 8.x
-- ============================================================
-- Ajusta únicamente los nombres de las FK si tus tablas usuarios,
-- categorias o cuentas utilizan otro nombre de clave primaria.
-- ============================================================

USE u992910078_finanzas_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS budget_accounts;
DROP TABLE IF EXISTS budget_categories;
DROP TABLE IF EXISTS budgets;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

-- ============================================================
-- 1. TABLA PRINCIPAL DE PRESUPUESTOS
-- ============================================================

CREATE TABLE budgets (
    id INT NOT NULL AUTO_INCREMENT,

    user_id INT NOT NULL,
    budget_series_id INT NULL,

    name VARCHAR(150) NOT NULL,

    -- Se calcula automáticamente sumando allocated_amount
    -- de las categorías relacionadas.
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    period ENUM(
        'weekly',
        'monthly',
        'yearly'
    ) NOT NULL,

    start_date DATE NOT NULL,
    end_date DATE NOT NULL,

    repeat_budget TINYINT(1) NOT NULL DEFAULT 0,

    status ENUM(
        'active',
        'paused',
        'archived'
    ) NOT NULL DEFAULT 'active',

    notes VARCHAR(500) NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    INDEX idx_budgets_user_period (
        user_id,
        start_date,
        end_date
    ),

    INDEX idx_budgets_series (
        budget_series_id
    ),

    CONSTRAINT fk_budgets_user
        FOREIGN KEY (user_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_budgets_series
        FOREIGN KEY (budget_series_id)
        REFERENCES budgets(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    CONSTRAINT chk_budgets_amount
        CHECK (amount >= 0),

    CONSTRAINT chk_budgets_dates
        CHECK (end_date >= start_date)

) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 2. CATEGORÍAS Y MONTO ASIGNADO A CADA CATEGORÍA
-- ============================================================

CREATE TABLE budget_categories (
    budget_id INT NOT NULL,
    category_id INT NOT NULL,

    allocated_amount DECIMAL(12,2) NOT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (
        budget_id,
        category_id
    ),

    INDEX idx_budget_categories_category (
        category_id
    ),

    CONSTRAINT fk_budget_categories_budget
        FOREIGN KEY (budget_id)
        REFERENCES budgets(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_budget_categories_category
        FOREIGN KEY (category_id)
        REFERENCES categorias(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT chk_budget_categories_amount
        CHECK (allocated_amount > 0)

) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- ============================================================
-- 3. CUENTAS ASOCIADAS AL PRESUPUESTO
-- ============================================================

CREATE TABLE budget_accounts (
    budget_id INT NOT NULL,
    account_id INT NOT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (
        budget_id,
        account_id
    ),

    INDEX idx_budget_accounts_account (
        account_id
    ),

    CONSTRAINT fk_budget_accounts_budget
        FOREIGN KEY (budget_id)
        REFERENCES budgets(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_budget_accounts_account
        FOREIGN KEY (account_id)
        REFERENCES cuentas(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

COMMIT;

-- ============================================================
-- MIGRACIÓN PARA UNA TABLA budgets YA EXISTENTE
-- Ejecuta estas sentencias SOLO si budgets ya existía.
-- MySQL puede fallar si la columna ya existe; en ese caso omítela.
-- ============================================================

-- ALTER TABLE budgets
--     ADD COLUMN repeat_budget TINYINT(1) NOT NULL DEFAULT 0
--     AFTER end_date;

-- ALTER TABLE budget_categories
--     ADD COLUMN allocated_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00
--     AFTER category_id;


-- ============================================================
-- CONSULTA DE CONTROL
-- El total guardado en budgets.amount debe coincidir con la suma
-- de los montos asignados a las categorías.
-- ============================================================

SELECT
    b.id,
    b.name,
    b.amount AS total_guardado,
    COALESCE(SUM(bc.allocated_amount), 0) AS total_categorias,
    b.amount - COALESCE(SUM(bc.allocated_amount), 0) AS diferencia
FROM budgets b
LEFT JOIN budget_categories bc
    ON bc.budget_id = b.id
GROUP BY b.id, b.name, b.amount
ORDER BY b.id DESC;

ALTER TABLE budgets DROP COLUMN period;