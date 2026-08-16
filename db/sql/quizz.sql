-- ============================================================
-- API QUIZ - TABLAS DE INTENTOS Y RESPUESTAS
-- Ejecutar UNA SOLA VEZ después del script learning.sql
-- ============================================================

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