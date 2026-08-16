-- ============================================================
-- EXTRA QUIZZES PARA LECCIONES
-- Ejecutar después de learning.sql
-- Agrega preguntas adicionales a las lecciones existentes del curso
-- "Regla 50/30/20" sin tocar el script base.
-- ============================================================

USE u992910078_finanzas_db;

SET @course_503020 = (
    SELECT id
    FROM courses
    WHERE title = 'Regla 50/30/20'
    LIMIT 1
);

SET @lesson_1 = (
    SELECT id
    FROM lessons
    WHERE course_id = @course_503020
      AND lesson_number = 1
    LIMIT 1
);

SET @lesson_2 = (
    SELECT id
    FROM lessons
    WHERE course_id = @course_503020
      AND lesson_number = 2
    LIMIT 1
);

SET @lesson_3 = (
    SELECT id
    FROM lessons
    WHERE course_id = @course_503020
      AND lesson_number = 3
    LIMIT 1
);

SET @lesson_4 = (
    SELECT id
    FROM lessons
    WHERE course_id = @course_503020
      AND lesson_number = 4
    LIMIT 1
);

SET @lesson_5 = (
    SELECT id
    FROM lessons
    WHERE course_id = @course_503020
      AND lesson_number = 5
    LIMIT 1
);


-- ============================================================
-- LECCIÓN 1 - ¿Qué es la regla 50/30/20?
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
    @lesson_1,
    '¿Cuál es el objetivo principal de la regla 50/30/20?',
    'La regla busca simplificar la distribución del dinero para equilibrar necesidades, deseos y ahorro.',
    1,
    1
);

SET @quiz_l1_1 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l1_1,
    'Organizar los ingresos para cubrir necesidades, deseos y ahorro',
    1,
    1
),
(
    @quiz_l1_1,
    'Aumentar siempre el gasto en entretenimiento',
    0,
    2
),
(
    @quiz_l1_1,
    'Evitar cualquier tipo de ahorro',
    0,
    3
),
(
    @quiz_l1_1,
    'Invertir todo el sueldo en una sola compra',
    0,
    4
);


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
    @lesson_1,
    'Si tus ingresos son S/ 2,500, ¿cuánto sería una buena aproximación para ahorro según la regla?',
    'El 20% de S/ 2,500 equivale a S/ 500.',
    2,
    1
);

SET @quiz_l1_2 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l1_2,
    'S/ 500',
    1,
    1
),
(
    @quiz_l1_2,
    'S/ 250',
    0,
    2
),
(
    @quiz_l1_2,
    'S/ 1,250',
    0,
    3
),
(
    @quiz_l1_2,
    'S/ 2,000',
    0,
    4
);


-- ============================================================
-- LECCIÓN 2 - Necesidades vs deseos
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
    @lesson_2,
    '¿Qué es un gasto de necesidad?',
    'Las necesidades son gastos esenciales para vivir y cubrir responsabilidades básicas.',
    1,
    1
);

SET @quiz_l2_1 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l2_1,
    'El alquiler o pago de vivienda',
    1,
    1
),
(
    @quiz_l2_1,
    'Un viaje de vacaciones no planificado',
    0,
    2
),
(
    @quiz_l2_1,
    'Comprar una consola nueva',
    0,
    3
),
(
    @quiz_l2_1,
    'Un restaurante caro cada fin de semana',
    0,
    4
);


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
    @lesson_2,
    '¿Cuál de estas opciones suele considerarse un deseo?',
    'Los deseos suelen ser gastos opcionales que pueden postergarse sin afectar la subsistencia básica.',
    2,
    1
);

SET @quiz_l2_2 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l2_2,
    'Salir a cenar con amigos un fin de semana',
    1,
    1
),
(
    @quiz_l2_2,
    'Pago de luz y agua',
    0,
    2
),
(
    @quiz_l2_2,
    'Compra de alimentos básicos',
    0,
    3
),
(
    @quiz_l2_2,
    'Gasto en transporte para ir al trabajo',
    0,
    4
);


-- ============================================================
-- LECCIÓN 3 - Cómo dividir tus ingresos
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
    @lesson_3,
    'Si ganas S/ 1,800 al mes, ¿cuántos soles aproximadamente podrías destinar a deseos según la regla 50/30/20?',
    'El 30% de S/ 1,800 equivale a S/ 540.',
    1,
    1
);

SET @quiz_l3_1 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l3_1,
    'S/ 540',
    1,
    1
),
(
    @quiz_l3_1,
    'S/ 900',
    0,
    2
),
(
    @quiz_l3_1,
    'S/ 360',
    0,
    3
),
(
    @quiz_l3_1,
    'S/ 1,080',
    0,
    4
);


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
    @lesson_3,
    '¿Qué porcentaje de tus ingresos te recomendaría ahorrar como mínimo según la regla 50/30/20?',
    'El porcentaje recomendado para ahorro es el 20%.',
    2,
    1
);

SET @quiz_l3_2 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l3_2,
    '20%',
    1,
    1
),
(
    @quiz_l3_2,
    '50%',
    0,
    2
),
(
    @quiz_l3_2,
    '10%',
    0,
    3
),
(
    @quiz_l3_2,
    '70%',
    0,
    4
);


-- ============================================================
-- LECCIÓN 4 - Errores comunes
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
    @lesson_4,
    '¿Cuál es un error común al manejar la regla 50/30/20?',
    'Un error frecuente es gastar en deseos y dejar de lado el ahorro programado.',
    1,
    1
);

SET @quiz_l4_1 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l4_1,
    'Usar el dinero del ahorro para compras impulsivas',
    1,
    1
),
(
    @quiz_l4_1,
    'Separar primero el ahorro al recibir el sueldo',
    0,
    2
),
(
    @quiz_l4_1,
    'Registrar los gastos mensuales',
    0,
    3
),
(
    @quiz_l4_1,
    'Establecer un presupuesto simple',
    0,
    4
);


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
    @lesson_4,
    '¿Por qué es importante registrar tus gastos?',
    'Registrar gastos permite comprobar si estás cumpliendo la proporción adecuada en cada categoría.',
    2,
    1
);

SET @quiz_l4_2 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l4_2,
    'Porque te ayuda a saber si estás gastando más de lo planeado',
    1,
    1
),
(
    @quiz_l4_2,
    'Porque no influye en el presupuesto',
    0,
    2
),
(
    @quiz_l4_2,
    'Porque reemplaza la necesidad de ahorrar',
    0,
    3
),
(
    @quiz_l4_2,
    'Porque elimina los deseos de compra',
    0,
    4
);


-- ============================================================
-- LECCIÓN 5 - Tu mini reto final
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
    '¿Cuál es la mejor forma de empezar a aplicar la regla 50/30/20?',
    'Lo ideal es definir primero tus gastos esenciales, luego tus deseos y finalmente el ahorro.',
    3,
    1
);

SET @quiz_l5_1 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l5_1,
    'Separar primero necesidades, luego deseos y finalmente ahorro',
    1,
    1
),
(
    @quiz_l5_1,
    'Gastar todo y ahorrar después de la quincena',
    0,
    2
),
(
    @quiz_l5_1,
    'No llevar registro de tus gastos',
    0,
    3
),
(
    @quiz_l5_1,
    'Asignar 100% al ahorro de inmediato',
    0,
    4
);


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
    '¿Qué te permite esta regla en la práctica?',
    'La regla ayuda a tomar decisiones más claras sobre tus finanzas sin complicarte demasiado.',
    4,
    1
);

SET @quiz_l5_2 = LAST_INSERT_ID();

INSERT INTO quiz_options
(
    quiz_id,
    option_text,
    is_correct,
    sort_order
)
VALUES
(
    @quiz_l5_2,
    'Una guía simple para distribuir el dinero y ganar control financiero',
    1,
    1
),
(
    @quiz_l5_2,
    'Aumentar todas tus deudas sin control',
    0,
    2
),
(
    @quiz_l5_2,
    'Evitar todo gasto de ocio y entretenimiento',
    0,
    3
),
(
    @quiz_l5_2,
    'Cambiar permanentemente tus ingresos',
    0,
    4
);

