USE u992910078_finanzas_db;

-- ============================================================
-- FINVIA - FAQ CORTA DE APRENDIZAJE / RESUELVE TUS DUDAS
-- ============================================================

CREATE TABLE IF NOT EXISTS learning_short_qna (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    question VARCHAR(255) NOT NULL,

    answer TEXT NOT NULL,

    category VARCHAR(50) NOT NULL DEFAULT 'GENERAL',

    sort_order INT NOT NULL DEFAULT 0,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_learning_short_qna_status (status),

    KEY idx_learning_short_qna_category (category),

    KEY idx_learning_short_qna_sort (sort_order)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;

INSERT INTO learning_short_qna (question, answer, category, sort_order, status)
VALUES
    (
        '¿Qué es un ingreso financiero?',
        'Un ingreso financiero es el dinero que entra a tu patrimonio, como sueldo, freelance, ventas, alquileres o intereses. Sirve como base para cubrir gastos, ahorrar e invertir.',
        'INGRESO',
        1,
        1
    ),
    (
        '¿Qué se considera un gasto financiero?',
        'Un gasto financiero es cualquier salida de dinero para cubrir necesidades o compromisos, como renta, comida, transporte, servicios, impuestos, deudas o compras importantes.',
        'GASTO',
        2,
        1
    ),
    (
        '¿Cómo puedo saber si estoy gastando más de lo que ingreso?',
        'Resta tus gastos totales de tus ingresos totales. Si el resultado es negativo, estás gastando más de lo que recibes. Una buena práctica es revisar tus movimientos por semana o mes.',
        'PRESUPUESTO',
        3,
        1
    ),
    (
        '¿Por qué es importante separar ingresos y gastos?',
        'Separarlos te permite controlar tu flujo de caja, identificar gastos innecesarios, evitar deudas y reservar dinero para ahorro o emergencias. También ayuda a tomar decisiones financieras más inteligentes.',
        'CONTROL',
        4,
        1
    ),
    (
        '¿Qué debo hacer si mi gasto fijo supera mi ingreso?',
        'Primero revisa tus gastos fijos y prioriza necesidades esenciales, luego busca reducir costos variables, renegociar deudas o aumentar tus ingresos. También puedes crear un presupuesto con tope mensual para no caer en déficit.',
        'PLANIFICACION',
        5,
        1
    )
ON DUPLICATE KEY UPDATE
    answer = VALUES(answer),
    category = VALUES(category),
    sort_order = VALUES(sort_order),
    status = VALUES(status);
