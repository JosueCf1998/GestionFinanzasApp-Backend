USE u992910078_finanzas_db;

-- ============================================================
-- FINVIA
-- MODULO APRENDER / EDUCACION FINANCIERA
-- ============================================================


-- ============================================================
-- 0. LIMPIEZA DE TABLAS DEL MODULO
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS quiz_options;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS user_learning_stats;
DROP TABLE IF EXISTS user_course_progress;
DROP TABLE IF EXISTS user_lesson_progress;
DROP TABLE IF EXISTS lesson_contents;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS learning_categories;

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- 1. CATEGORIAS DE APRENDIZAJE
-- ============================================================

CREATE TABLE learning_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    name VARCHAR(100) NOT NULL,

    description VARCHAR(255) NULL,

    icon VARCHAR(100) NULL,

    sort_order INT NOT NULL DEFAULT 0,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_learning_categories_name (name),

    KEY idx_learning_categories_status (status),

    KEY idx_learning_categories_sort (sort_order)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. CURSOS
-- ============================================================

CREATE TABLE courses (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    category_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(150) NOT NULL,

    description VARCHAR(500) NULL,

    image_url VARCHAR(500) NULL,

    level ENUM(
        'BASICO',
        'INTERMEDIO',
        'AVANZADO'
    ) NOT NULL DEFAULT 'BASICO',

    estimated_minutes INT UNSIGNED NOT NULL DEFAULT 0,

    xp_reward INT UNSIGNED NOT NULL DEFAULT 0,

    is_featured TINYINT(1) NOT NULL DEFAULT 0,

    sort_order INT NOT NULL DEFAULT 0,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_courses_category (category_id),

    KEY idx_courses_level (level),

    KEY idx_courses_featured (is_featured),

    KEY idx_courses_status (status),

    KEY idx_courses_sort (sort_order),

    CONSTRAINT fk_courses_category
        FOREIGN KEY (category_id)
        REFERENCES learning_categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. LECCIONES
-- ============================================================

CREATE TABLE lessons (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    course_id BIGINT UNSIGNED NOT NULL,

    lesson_number INT UNSIGNED NOT NULL,

    title VARCHAR(200) NOT NULL,

    description VARCHAR(500) NULL,

    estimated_minutes INT UNSIGNED NOT NULL DEFAULT 0,

    xp_reward INT UNSIGNED NOT NULL DEFAULT 20,

    image_url VARCHAR(500) NULL,

    sort_order INT NOT NULL DEFAULT 0,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_course_lesson_number (
        course_id,
        lesson_number
    ),

    KEY idx_lessons_course (course_id),

    KEY idx_lessons_status (status),

    KEY idx_lessons_sort (
        course_id,
        sort_order
    ),

    CONSTRAINT fk_lessons_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. CONTENIDO DE LECCIONES
-- ============================================================

CREATE TABLE lesson_contents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    lesson_id BIGINT UNSIGNED NOT NULL,

    content_type ENUM(
        'TITLE',
        'TEXT',
        'IMAGE',
        'INFO',
        'EXAMPLE',
        'TIP',
        'WARNING'
    ) NOT NULL,

    title VARCHAR(255) NULL,

    content TEXT NULL,

    image_url VARCHAR(500) NULL,

    sort_order INT NOT NULL DEFAULT 0,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_lesson_contents_lesson (
        lesson_id
    ),

    KEY idx_lesson_contents_order (
        lesson_id,
        sort_order
    ),

    CONSTRAINT fk_lesson_contents_lesson
        FOREIGN KEY (lesson_id)
        REFERENCES lessons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. PROGRESO POR LECCION
-- IMPORTANTE:
-- usuarios.id = INT
-- por eso user_id también debe ser INT
-- ============================================================

CREATE TABLE user_lesson_progress (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id INT NOT NULL,

    lesson_id BIGINT UNSIGNED NOT NULL,

    status ENUM(
        'NOT_STARTED',
        'IN_PROGRESS',
        'COMPLETED'
    ) NOT NULL DEFAULT 'NOT_STARTED',

    progress_percent DECIMAL(5,2)
        NOT NULL DEFAULT 0.00,

    started_at DATETIME NULL,

    completed_at DATETIME NULL,

    xp_earned INT UNSIGNED
        NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_user_lesson (
        user_id,
        lesson_id
    ),

    KEY idx_user_lesson_user (
        user_id
    ),

    KEY idx_user_lesson_lesson (
        lesson_id
    ),

    KEY idx_user_lesson_status (
        status
    ),

    CONSTRAINT fk_user_lesson_progress_user
        FOREIGN KEY (user_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_lesson_progress_lesson
        FOREIGN KEY (lesson_id)
        REFERENCES lessons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. PROGRESO POR CURSO
-- ============================================================

CREATE TABLE user_course_progress (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id INT NOT NULL,

    course_id BIGINT UNSIGNED NOT NULL,

    completed_lessons INT UNSIGNED
        NOT NULL DEFAULT 0,

    total_lessons INT UNSIGNED
        NOT NULL DEFAULT 0,

    progress_percent DECIMAL(5,2)
        NOT NULL DEFAULT 0.00,

    status ENUM(
        'NOT_STARTED',
        'IN_PROGRESS',
        'COMPLETED'
    ) NOT NULL DEFAULT 'NOT_STARTED',

    started_at DATETIME NULL,

    completed_at DATETIME NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_user_course (
        user_id,
        course_id
    ),

    KEY idx_user_course_user (
        user_id
    ),

    KEY idx_user_course_course (
        course_id
    ),

    KEY idx_user_course_status (
        status
    ),

    CONSTRAINT fk_user_course_progress_user
        FOREIGN KEY (user_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_course_progress_course
        FOREIGN KEY (course_id)
        REFERENCES courses(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. ESTADISTICAS DE APRENDIZAJE
-- ============================================================

CREATE TABLE user_learning_stats (
    user_id INT NOT NULL,

    total_xp INT UNSIGNED
        NOT NULL DEFAULT 0,

    current_level ENUM(
        'PRINCIPIANTE',
        'INTERMEDIO',
        'AVANZADO',
        'EXPERTO'
    ) NOT NULL DEFAULT 'PRINCIPIANTE',

    current_streak INT UNSIGNED
        NOT NULL DEFAULT 0,

    longest_streak INT UNSIGNED
        NOT NULL DEFAULT 0,

    last_activity_date DATE NULL,

    completed_lessons INT UNSIGNED
        NOT NULL DEFAULT 0,

    completed_courses INT UNSIGNED
        NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),

    CONSTRAINT fk_user_learning_stats_user
        FOREIGN KEY (user_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. QUIZZES
-- ============================================================

CREATE TABLE quizzes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    lesson_id BIGINT UNSIGNED NOT NULL,

    question TEXT NOT NULL,

    explanation TEXT NULL,

    sort_order INT NOT NULL DEFAULT 0,

    status TINYINT(1) NOT NULL DEFAULT 1,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_quizzes_lesson (
        lesson_id
    ),

    KEY idx_quizzes_order (
        lesson_id,
        sort_order
    ),

    CONSTRAINT fk_quizzes_lesson
        FOREIGN KEY (lesson_id)
        REFERENCES lessons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. OPCIONES DE QUIZ
-- ============================================================

CREATE TABLE quiz_options (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    quiz_id BIGINT UNSIGNED NOT NULL,

    option_text VARCHAR(500) NOT NULL,

    is_correct TINYINT(1) NOT NULL DEFAULT 0,

    sort_order INT NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_quiz_options_quiz (
        quiz_id
    ),

    KEY idx_quiz_options_order (
        quiz_id,
        sort_order
    ),

    CONSTRAINT fk_quiz_options_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. CATEGORIAS INICIALES
-- ============================================================

INSERT INTO learning_categories
(
    name,
    description,
    icon,
    sort_order,
    status
)
VALUES
(
    'Básico',
    'Conceptos fundamentales para comenzar a gestionar tus finanzas personales.',
    'school',
    1,
    1
),
(
    'Presupuestos',
    'Aprende a organizar, planificar y distribuir correctamente tu dinero.',
    'chart-pie',
    2,
    1
),
(
    'Ahorro',
    'Aprende técnicas y hábitos para mejorar tu capacidad de ahorro.',
    'piggy-bank',
    3,
    1
),
(
    'Deudas',
    'Aprende a administrar créditos, tarjetas y obligaciones financieras.',
    'credit-card',
    4,
    1
),
(
    'Inversión',
    'Conoce los conceptos fundamentales sobre inversión, riesgo y rentabilidad.',
    'trending-up',
    5,
    1
);


-- ============================================================
-- 11. CURSO: REGLA 50/30/20
-- ============================================================

INSERT INTO courses
(
    category_id,
    title,
    description,
    image_url,
    level,
    estimated_minutes,
    xp_reward,
    is_featured,
    sort_order,
    status
)
VALUES
(
    (
        SELECT id
        FROM learning_categories
        WHERE name = 'Básico'
        LIMIT 1
    ),
    'Regla 50/30/20',
    'Aprende a distribuir tus ingresos de forma inteligente.',
    'learning/regla-50-30-20.png',
    'BASICO',
    12,
    100,
    1,
    1,
    1
);

SET @course_503020 = LAST_INSERT_ID();


-- ============================================================
-- 12. LECCION 1
-- ============================================================

INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_503020,
    1,
    '¿Qué es la regla 50/30/20?',
    'Conoce qué significa la regla 50/30/20 y cómo puede ayudarte a organizar tus ingresos.',
    2,
    20,
    1,
    1
);

SET @lesson_1 = LAST_INSERT_ID();


INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @lesson_1,
    'TITLE',
    'Regla 50/30/20',
    'Una forma sencilla de organizar tus ingresos.',
    NULL,
    1
),
(
    @lesson_1,
    'TEXT',
    NULL,
    'La regla 50/30/20 propone dividir tus ingresos en tres grandes grupos: necesidades, deseos y ahorro.',
    NULL,
    2
),
(
    @lesson_1,
    'INFO',
    '50% Necesidades',
    'Destina aproximadamente la mitad de tus ingresos a gastos esenciales.',
    NULL,
    3
),
(
    @lesson_1,
    'INFO',
    '30% Deseos',
    'Destina una parte de tus ingresos a gastos personales que mejoran tu calidad de vida.',
    NULL,
    4
),
(
    @lesson_1,
    'INFO',
    '20% Ahorro',
    'Reserva una parte de tus ingresos para metas, emergencias y futuro.',
    NULL,
    5
);


-- ============================================================
-- 13. LECCION 2
-- ============================================================

INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_503020,
    2,
    'Necesidades vs deseos',
    'Aprende a diferenciar los gastos esenciales de los gastos opcionales.',
    3,
    20,
    2,
    1
);

SET @lesson_2 = LAST_INSERT_ID();


INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @lesson_2,
    'TITLE',
    'Necesidades y deseos',
    'Antes de gastar, aprende a identificar qué es realmente necesario.',
    NULL,
    1
),
(
    @lesson_2,
    'INFO',
    'Necesidades',
    'Son gastos esenciales como alimentación, vivienda, servicios básicos, transporte y salud.',
    NULL,
    2
),
(
    @lesson_2,
    'INFO',
    'Deseos',
    'Son gastos que podemos reducir o postergar, como entretenimiento, compras no esenciales o salidas.',
    NULL,
    3
),
(
    @lesson_2,
    'TIP',
    'Consejo',
    'Antes de comprar algo, pregúntate si realmente lo necesitas o simplemente lo deseas.',
    NULL,
    4
);


-- ============================================================
-- 14. LECCION 3
-- ============================================================

INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_503020,
    3,
    'Cómo dividir tus ingresos',
    'Aprende a distribuir tus ingresos utilizando la regla 50/30/20.',
    3,
    20,
    3,
    1
);

SET @lesson_3 = LAST_INSERT_ID();


INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @lesson_3,
    'IMAGE',
    NULL,
    NULL,
    'learning/503020.png',
    1
),
(
    @lesson_3,
    'TEXT',
    NULL,
    'La regla 50/30/20 te ayuda a organizar tu dinero de forma simple y efectiva.',
    NULL,
    2
),
(
    @lesson_3,
    'INFO',
    '50% Necesidades',
    'Gastos esenciales del día a día.',
    NULL,
    3
),
(
    @lesson_3,
    'INFO',
    '30% Deseos',
    'Gastos que mejoran tu calidad de vida.',
    NULL,
    4
),
(
    @lesson_3,
    'INFO',
    '20% Ahorro',
    'Dinero destinado a tu futuro, metas y emergencias.',
    NULL,
    5
),
(
    @lesson_3,
    'EXAMPLE',
    'Ejemplo: Si ganas S/ 2,000',
    'Necesidades: S/ 1,000 | Deseos: S/ 600 | Ahorro: S/ 400',
    NULL,
    6
);


-- ============================================================
-- 15. LECCION 4
-- ============================================================

INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_503020,
    4,
    'Errores comunes',
    'Conoce los errores más frecuentes al utilizar la regla 50/30/20.',
    2,
    20,
    4,
    1
);

SET @lesson_4 = LAST_INSERT_ID();


INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @lesson_4,
    'WARNING',
    'Gastar demasiado en deseos',
    'Uno de los errores más frecuentes es utilizar dinero destinado al ahorro para compras no esenciales.',
    NULL,
    1
),
(
    @lesson_4,
    'WARNING',
    'No registrar tus gastos',
    'Si no registras tus movimientos será más difícil saber cuánto estás destinando a cada grupo.',
    NULL,
    2
),
(
    @lesson_4,
    'WARNING',
    'No ahorrar al recibir ingresos',
    'Evita esperar hasta fin de mes para ahorrar. Separa el ahorro desde que recibes tus ingresos.',
    NULL,
    3
),
(
    @lesson_4,
    'TIP',
    'Recuerda',
    'Los porcentajes son una guía y pueden adaptarse a tu realidad financiera.',
    NULL,
    4
);


-- ============================================================
-- 16. LECCION 5
-- ============================================================

INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_503020,
    5,
    'Tu mini reto final',
    'Pon en práctica lo aprendido en el curso.',
    2,
    50,
    5,
    1
);

SET @lesson_5 = LAST_INSERT_ID();


INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @lesson_5,
    'TITLE',
    'Mini reto',
    'Ahora aplica la regla 50/30/20 a un caso práctico.',
    NULL,
    1
),
(
    @lesson_5,
    'EXAMPLE',
    'Reto',
    'Imagina que recibes S/ 1,500 mensuales. Calcula cuánto deberías destinar a necesidades, deseos y ahorro.',
    NULL,
    2
),
(
    @lesson_5,
    'TIP',
    'Pista',
    'Calcula el 50%, 30% y 20% de S/ 1,500.',
    NULL,
    3
);


-- ============================================================
-- 17. QUIZ DE LA LECCION FINAL
-- ============================================================

INSERT INTO quizzes
(
    lesson_id,
    question,
    explanation,
    sort_order,
    status
)
VALUES
(
    @lesson_5,
    'Si recibes S/ 1,500 al mes, ¿cuánto corresponde al ahorro aplicando la regla 50/30/20?',
    'El 20% de S/ 1,500 equivale a S/ 300.',
    1,
    1
);

SET @quiz_1 = LAST_INSERT_ID();


INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_1,
    'S/ 150',
    0,
    1
),
(
    @quiz_1,
    'S/ 300',
    1,
    2
),
(
    @quiz_1,
    'S/ 450',
    0,
    3
),
(
    @quiz_1,
    'S/ 750',
    0,
    4
);


-- ============================================================
-- 18. SEGUNDA PREGUNTA DEL QUIZ
-- ============================================================

INSERT INTO quizzes
(
    lesson_id,
    question,
    explanation,
    sort_order,
    status
)
VALUES
(
    @lesson_5,
    '¿Qué porcentaje de la regla 50/30/20 corresponde a necesidades?',
    'La regla recomienda utilizar aproximadamente el 50% para necesidades.',
    2,
    1
);

SET @quiz_2 = LAST_INSERT_ID();


INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_2,
    '20%',
    0,
    1
),
(
    @quiz_2,
    '30%',
    0,
    2
),
(
    @quiz_2,
    '50%',
    1,
    3
),
(
    @quiz_2,
    '70%',
    0,
    4
);


-- ============================================================
-- 19. CURSO: PRESUPUESTO SIN COMPLICACIONES
-- ============================================================

INSERT INTO courses
(
    category_id,
    title,
    description,
    image_url,
    level,
    estimated_minutes,
    xp_reward,
    is_featured,
    sort_order,
    status
)
VALUES
(
    (
        SELECT id
        FROM learning_categories
        WHERE name = 'Presupuestos'
        LIMIT 1
    ),
    'Presupuesto sin complicaciones',
    'Aprende a planificar tu dinero con un presupuesto simple y realista.',
    'learning/presupuesto-simple.png',
    'BASICO',
    14,
    110,
    0,
    2,
    1
);

SET @course_budget = LAST_INSERT_ID();


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_budget,
    1,
    'Por que hacer un presupuesto',
    'Descubre como un presupuesto te ayuda a tomar control de tus gastos.',
    3,
    20,
    1,
    1
);

SET @budget_lesson_1 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @budget_lesson_1,
    'TITLE',
    'Tu dinero necesita un plan',
    'Un presupuesto te dice a donde va tu dinero antes de que desaparezca.',
    NULL,
    1
),
(
    @budget_lesson_1,
    'TEXT',
    NULL,
    'Presupuestar no es restringirte, es decidir conscientemente como usar tus ingresos.',
    NULL,
    2
),
(
    @budget_lesson_1,
    'TIP',
    'Empieza facil',
    'Comienza revisando solo tus gastos fijos y tus gastos variables mas comunes.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_budget,
    2,
    'Gastos fijos y variables',
    'Aprende a separar tus gastos para que tu presupuesto sea mas claro.',
    3,
    20,
    2,
    1
);

SET @budget_lesson_2 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @budget_lesson_2,
    'INFO',
    'Gastos fijos',
    'Son pagos que se repiten con poca variacion, como alquiler, internet o transporte regular.',
    NULL,
    1
),
(
    @budget_lesson_2,
    'INFO',
    'Gastos variables',
    'Son gastos que cambian cada mes, como ocio, comidas fuera de casa o compras impulsivas.',
    NULL,
    2
),
(
    @budget_lesson_2,
    'EXAMPLE',
    'Ejemplo',
    'Si ganas S/ 2,200 y tus gastos fijos son S/ 1,200, sabes cuanto espacio real tienes para ajustar.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_budget,
    3,
    'Arma un presupuesto base',
    'Construye una primera version sencilla de tu presupuesto mensual.',
    4,
    20,
    3,
    1
);

SET @budget_lesson_3 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @budget_lesson_3,
    'TEXT',
    NULL,
    'Anota tus ingresos del mes y reparte montos maximos por categoria antes de empezar a gastar.',
    NULL,
    1
),
(
    @budget_lesson_3,
    'TIP',
    'No persigas la perfeccion',
    'Tu primer presupuesto puede fallar. Lo importante es que te de una base para ajustar el siguiente mes.',
    NULL,
    2
),
(
    @budget_lesson_3,
    'INFO',
    'Checklist',
    'Incluye vivienda, transporte, comida, ahorro y un margen pequeno para imprevistos.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_budget,
    4,
    'Reto: crea tu presupuesto semanal',
    'Aplica lo aprendido con un reto corto y accionable.',
    4,
    50,
    4,
    1
);

SET @budget_lesson_4 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @budget_lesson_4,
    'TITLE',
    'Tu reto',
    'Durante una semana registra todos tus gastos y comparalos con un tope previo.',
    NULL,
    1
),
(
    @budget_lesson_4,
    'EXAMPLE',
    'Objetivo',
    'Define montos maximos para transporte, comida y gastos personales durante 7 dias.',
    NULL,
    2
),
(
    @budget_lesson_4,
    'TIP',
    'Cierre',
    'Al final de la semana identifica en que categoria te desviaste y por que.',
    NULL,
    3
);


-- ============================================================
-- 20. CURSO: FONDO DE EMERGENCIA DESDE CERO
-- ============================================================

INSERT INTO courses
(
    category_id,
    title,
    description,
    image_url,
    level,
    estimated_minutes,
    xp_reward,
    is_featured,
    sort_order,
    status
)
VALUES
(
    (
        SELECT id
        FROM learning_categories
        WHERE name = 'Ahorro'
        LIMIT 1
    ),
    'Fondo de emergencia desde cero',
    'Construye un colchon financiero para enfrentar imprevistos sin endeudarte.',
    'learning/fondo-emergencia.png',
    'BASICO',
    13,
    110,
    0,
    3,
    1
);

SET @course_savings = LAST_INSERT_ID();


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_savings,
    1,
    'Que es un fondo de emergencia',
    'Entiende para que sirve y cuando deberias usarlo.',
    3,
    20,
    1,
    1
);

SET @savings_lesson_1 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @savings_lesson_1,
    'TEXT',
    NULL,
    'Un fondo de emergencia es dinero reservado para eventos inesperados como una enfermedad o una reparacion urgente.',
    NULL,
    1
),
(
    @savings_lesson_1,
    'WARNING',
    'No es para gustos',
    'No deberia usarse para compras planeadas ni para aprovechar ofertas.',
    NULL,
    2
),
(
    @savings_lesson_1,
    'TIP',
    'Empieza pequeno',
    'Tu primer objetivo puede ser ahorrar el equivalente a una semana de gastos basicos.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_savings,
    2,
    'Cuanto deberias ahorrar',
    'Aprende a fijar una meta realista para tu fondo.',
    3,
    20,
    2,
    1
);

SET @savings_lesson_2 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @savings_lesson_2,
    'INFO',
    'Meta inicial',
    'Muchas personas comienzan con un objetivo equivalente a un mes de gastos esenciales.',
    NULL,
    1
),
(
    @savings_lesson_2,
    'INFO',
    'Meta intermedia',
    'El objetivo mas solido es acumular de tres a seis meses de gastos basicos.',
    NULL,
    2
),
(
    @savings_lesson_2,
    'EXAMPLE',
    'Ejemplo',
    'Si tus gastos esenciales son S/ 1,200, una meta inicial de tres meses seria S/ 3,600.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_savings,
    3,
    'Donde guardar tu fondo',
    'Elige una opcion segura y accesible para tu ahorro.',
    3,
    20,
    3,
    1
);

SET @savings_lesson_3 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @savings_lesson_3,
    'INFO',
    'Liquidez primero',
    'Tu fondo debe estar disponible rapidamente, por eso conviene usar cuentas de ahorro o instrumentos de bajo riesgo.',
    NULL,
    1
),
(
    @savings_lesson_3,
    'WARNING',
    'Evita bloquearlo',
    'No coloques tu fondo de emergencia en productos que penalicen el retiro anticipado.',
    NULL,
    2
),
(
    @savings_lesson_3,
    'TIP',
    'Separalo visualmente',
    'Usa una cuenta distinta para no mezclarlo con el dinero del dia a dia.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_savings,
    4,
    'Reto: tu primer ahorro automatico',
    'Convierte el ahorro en un habito automatico.',
    4,
    50,
    4,
    1
);

SET @savings_lesson_4 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @savings_lesson_4,
    'TITLE',
    'Activa un sistema',
    'Programa una transferencia pequena hacia tu fondo apenas recibas ingresos.',
    NULL,
    1
),
(
    @savings_lesson_4,
    'EXAMPLE',
    'Ejemplo',
    'Si recibes pago quincenal, mueve S/ 50 o S/ 100 el mismo dia a una cuenta separada.',
    NULL,
    2
),
(
    @savings_lesson_4,
    'TIP',
    'Hazlo sostenible',
    'Es mejor ahorrar un monto pequeno constante que fijar un monto alto imposible de mantener.',
    NULL,
    3
);


-- ============================================================
-- 21. CURSO: ORDENA Y PAGA TUS DEUDAS
-- ============================================================

INSERT INTO courses
(
    category_id,
    title,
    description,
    image_url,
    level,
    estimated_minutes,
    xp_reward,
    is_featured,
    sort_order,
    status
)
VALUES
(
    (
        SELECT id
        FROM learning_categories
        WHERE name = 'Deudas'
        LIMIT 1
    ),
    'Ordena y paga tus deudas',
    'Aprende a priorizar deudas y evitar que los intereses te ahoguen.',
    'learning/deudas-control.png',
    'INTERMEDIO',
    15,
    110,
    0,
    4,
    1
);

SET @course_debts = LAST_INSERT_ID();


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_debts,
    1,
    'Entiende tus deudas',
    'Haz un inventario claro de saldos, cuotas e intereses.',
    4,
    20,
    1,
    1
);

SET @debts_lesson_1 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @debts_lesson_1,
    'TEXT',
    NULL,
    'El primer paso no es pagar mas rapido, sino saber exactamente cuanto debes, a quien y bajo que condiciones.',
    NULL,
    1
),
(
    @debts_lesson_1,
    'INFO',
    'Datos minimos',
    'Registra saldo total, cuota minima, tasa de interes y fecha de pago.',
    NULL,
    2
),
(
    @debts_lesson_1,
    'TIP',
    'Orden visual',
    'Una tabla simple en papel o en una hoja de calculo es suficiente para empezar.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_debts,
    2,
    'Metodo bola de nieve vs avalancha',
    'Compara dos estrategias populares para salir de deudas.',
    4,
    20,
    2,
    1
);

SET @debts_lesson_2 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @debts_lesson_2,
    'INFO',
    'Bola de nieve',
    'Pagas primero la deuda mas pequena para ganar impulso y motivacion.',
    NULL,
    1
),
(
    @debts_lesson_2,
    'INFO',
    'Avalancha',
    'Pagas primero la deuda con mayor interes para reducir el costo total.',
    NULL,
    2
),
(
    @debts_lesson_2,
    'TIP',
    'El mejor metodo es el que sostienes',
    'Si necesitas motivacion rapida, bola de nieve. Si te enfocan los numeros, avalancha.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_debts,
    3,
    'Evita nuevas deudas',
    'Corta los patrones que vuelven a generar endeudamiento.',
    3,
    20,
    3,
    1
);

SET @debts_lesson_3 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @debts_lesson_3,
    'WARNING',
    'El problema no siempre es la cuota',
    'Si sigues usando credito para cubrir deficits mensuales, la deuda reaparece aunque hagas pagos puntuales.',
    NULL,
    1
),
(
    @debts_lesson_3,
    'TIP',
    'Ataca la causa',
    'Revisa presupuesto, gastos impulsivos y compras por presion social.',
    NULL,
    2
),
(
    @debts_lesson_3,
    'INFO',
    'Regla practica',
    'Mientras sales de deudas, evita asumir nuevas cuotas salvo emergencias reales.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_debts,
    4,
    'Reto: plan de pago de 30 dias',
    'Define un plan corto para avanzar sobre tu deuda principal.',
    4,
    50,
    4,
    1
);

SET @debts_lesson_4 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @debts_lesson_4,
    'TITLE',
    'Tu reto',
    'Elige una deuda y compromete un pago extra durante 30 dias.',
    NULL,
    1
),
(
    @debts_lesson_4,
    'EXAMPLE',
    'Paso concreto',
    'Puedes redirigir un gasto prescindible semanal para sumarlo a la cuota minima.',
    NULL,
    2
),
(
    @debts_lesson_4,
    'TIP',
    'Mide el avance',
    'Anota el saldo antes y despues del mes para visualizar la reduccion real.',
    NULL,
    3
);


-- ============================================================
-- 22. CURSO: PRIMEROS PASOS PARA INVERTIR
-- ============================================================

INSERT INTO courses
(
    category_id,
    title,
    description,
    image_url,
    level,
    estimated_minutes,
    xp_reward,
    is_featured,
    sort_order,
    status
)
VALUES
(
    (
        SELECT id
        FROM learning_categories
        WHERE name = 'Inversión'
        LIMIT 1
    ),
    'Primeros pasos para invertir',
    'Conoce conceptos basicos de riesgo, plazo y diversificacion antes de invertir.',
    'learning/inversion-primeros-pasos.png',
    'BASICO',
    16,
    110,
    0,
    5,
    1
);

SET @course_investing = LAST_INSERT_ID();


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_investing,
    1,
    'Antes de invertir',
    'Revisa que condiciones deberias cumplir antes de empezar.',
    4,
    20,
    1,
    1
);

SET @invest_lesson_1 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @invest_lesson_1,
    'WARNING',
    'No empieces con prisa',
    'Invertir sin fondo de emergencia o con deudas costosas suele aumentar tu riesgo financiero.',
    NULL,
    1
),
(
    @invest_lesson_1,
    'INFO',
    'Base minima',
    'Lo ideal es tener presupuesto, ahorro y objetivos claros antes de invertir.',
    NULL,
    2
),
(
    @invest_lesson_1,
    'TIP',
    'Define tu objetivo',
    'Invertir para una meta de un ano no es lo mismo que invertir para retiro.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_investing,
    2,
    'Riesgo y plazo',
    'Comprende la relacion entre tiempo, volatilidad y rentabilidad esperada.',
    4,
    20,
    2,
    1
);

SET @invest_lesson_2 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @invest_lesson_2,
    'INFO',
    'Mas retorno, mas variacion',
    'Activos con mayor potencial de retorno normalmente exigen tolerar fluctuaciones mayores.',
    NULL,
    1
),
(
    @invest_lesson_2,
    'INFO',
    'El plazo importa',
    'A mayor horizonte, mas margen tienes para soportar caidas temporales.',
    NULL,
    2
),
(
    @invest_lesson_2,
    'EXAMPLE',
    'Ejemplo',
    'Un objetivo de 10 anos admite mas riesgo que una meta para dentro de 6 meses.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_investing,
    3,
    'Diversificacion basica',
    'Reduce riesgo evitando concentrar todo en un solo activo.',
    4,
    20,
    3,
    1
);

SET @invest_lesson_3 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @invest_lesson_3,
    'TEXT',
    NULL,
    'Diversificar significa repartir tu dinero entre opciones distintas para que un solo error no afecte todo tu capital.',
    NULL,
    1
),
(
    @invest_lesson_3,
    'TIP',
    'Piensa en equilibrio',
    'Diversificar no elimina el riesgo, pero evita depender de una sola apuesta.',
    NULL,
    2
),
(
    @invest_lesson_3,
    'WARNING',
    'Cuidado con modas',
    'No inviertas solo porque un activo esta en tendencia o porque otros dicen que subira.',
    NULL,
    3
);


INSERT INTO lessons
(
    course_id,
    lesson_number,
    title,
    description,
    estimated_minutes,
    xp_reward,
    sort_order,
    status
)
VALUES
(
    @course_investing,
    4,
    'Reto: disena tu perfil inversionista',
    'Reflexiona sobre plazo, objetivo y tolerancia al riesgo.',
    4,
    50,
    4,
    1
);

SET @invest_lesson_4 = LAST_INSERT_ID();

INSERT INTO lesson_contents
(
    lesson_id,
    content_type,
    title,
    content,
    image_url,
    sort_order
)
VALUES
(
    @invest_lesson_4,
    'TITLE',
    'Tu reto',
    'Describe para que invertirias, en que plazo y cuanto riesgo estas dispuesto a tolerar.',
    NULL,
    1
),
(
    @invest_lesson_4,
    'EXAMPLE',
    'Ejemplo',
    'Meta: inicial de vivienda en 5 anos, con aportes mensuales constantes y tolerancia moderada al riesgo.',
    NULL,
    2
),
(
    @invest_lesson_4,
    'TIP',
    'No copies perfiles ajenos',
    'Tu estrategia debe responder a tu situacion financiera y no a la de otra persona.',
    NULL,
    3
);


-- ============================================================
-- 23. CREAR ESTADISTICAS PARA USUARIOS EXISTENTES
-- ============================================================

INSERT INTO user_learning_stats
(
    user_id,
    total_xp,
    current_level,
    current_streak,
    longest_streak,
    completed_lessons,
    completed_courses
)
SELECT
    id,
    0,
    'PRINCIPIANTE',
    0,
    0,
    0,
    0
FROM usuarios;


CREATE TABLE IF NOT EXISTS user_quiz_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    user_id INT NOT NULL,
    lesson_id BIGINT UNSIGNED NOT NULL,

    attempt_number INT UNSIGNED NOT NULL,

    total_questions INT UNSIGNED NOT NULL,
    correct_answers INT UNSIGNED NOT NULL,

    score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    passed TINYINT(1) NOT NULL DEFAULT 0,

    completed_at DATETIME NOT NULL,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_user_lesson_attempt (
        user_id,
        lesson_id,
        attempt_number
    ),

    KEY idx_quiz_attempt_user (
        user_id
    ),

    KEY idx_quiz_attempt_lesson (
        lesson_id
    ),

    KEY idx_quiz_attempt_passed (
        user_id,
        lesson_id,
        passed
    ),

    CONSTRAINT fk_user_quiz_attempt_user
        FOREIGN KEY (user_id)
        REFERENCES usuarios(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_quiz_attempt_lesson
        FOREIGN KEY (lesson_id)
        REFERENCES lessons(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS user_quiz_answers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

    attempt_id BIGINT UNSIGNED NOT NULL,
    quiz_id BIGINT UNSIGNED NOT NULL,
    selected_option_id BIGINT UNSIGNED NOT NULL,

    is_correct TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uk_attempt_quiz (
        attempt_id,
        quiz_id
    ),

    KEY idx_quiz_answer_attempt (
        attempt_id
    ),

    KEY idx_quiz_answer_quiz (
        quiz_id
    ),

    KEY idx_quiz_answer_option (
        selected_option_id
    ),

    CONSTRAINT fk_user_quiz_answer_attempt
        FOREIGN KEY (attempt_id)
        REFERENCES user_quiz_attempts(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_quiz_answer_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_user_quiz_answer_option
        FOREIGN KEY (selected_option_id)
        REFERENCES quiz_options(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;