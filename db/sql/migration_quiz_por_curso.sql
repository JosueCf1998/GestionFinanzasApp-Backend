-- ============================================================
-- MIGRACION: Quiz por curso (ya no por leccion)
-- ============================================================
-- Objetivo:
-- 1) Mover quizzes.lesson_id -> quizzes.course_id
-- 2) Mover user_quiz_attempts.lesson_id -> user_quiz_attempts.course_id
-- 3) Mantener los datos actuales migrando course_id desde lessons
--
-- Recomendacion:
-- Ejecutar en ventana de mantenimiento y con backup previo.

SET @OLD_SQL_SAFE_UPDATES := @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

START TRANSACTION;

-- ------------------------------------------------------------
-- A) quizzes: de lesson_id a course_id
-- ------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND COLUMN_NAME = 'course_id'
        ),
        'SELECT 1',
        'ALTER TABLE quizzes ADD COLUMN course_id BIGINT UNSIGNED NULL AFTER id'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND COLUMN_NAME = 'lesson_id'
        )
        AND EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND COLUMN_NAME = 'course_id'
        ),
        'UPDATE quizzes q INNER JOIN lessons l ON l.id = q.lesson_id SET q.course_id = l.course_id WHERE q.id IS NOT NULL AND q.course_id IS NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND CONSTRAINT_NAME = 'fk_quizzes_lesson'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        'ALTER TABLE quizzes DROP FOREIGN KEY fk_quizzes_lesson',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND INDEX_NAME = 'idx_quizzes_order'
        ),
        'ALTER TABLE quizzes DROP INDEX idx_quizzes_order',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND INDEX_NAME = 'idx_quizzes_lesson'
        ),
        'ALTER TABLE quizzes DROP INDEX idx_quizzes_lesson',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND COLUMN_NAME = 'course_id'
              AND IS_NULLABLE = 'YES'
        ),
        'ALTER TABLE quizzes MODIFY COLUMN course_id BIGINT UNSIGNED NOT NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND INDEX_NAME = 'idx_quizzes_course'
        ),
        'SELECT 1',
        'ALTER TABLE quizzes ADD KEY idx_quizzes_course (course_id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND INDEX_NAME = 'idx_quizzes_course_order'
        ),
        'SELECT 1',
        'ALTER TABLE quizzes ADD KEY idx_quizzes_course_order (course_id, sort_order)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND CONSTRAINT_NAME = 'fk_quizzes_course'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        'SELECT 1',
        'ALTER TABLE quizzes ADD CONSTRAINT fk_quizzes_course FOREIGN KEY (course_id) REFERENCES courses(id) ON UPDATE CASCADE ON DELETE CASCADE'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'quizzes'
              AND COLUMN_NAME = 'lesson_id'
        ),
        'ALTER TABLE quizzes DROP COLUMN lesson_id',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- B) user_quiz_attempts: de lesson_id a course_id
-- ------------------------------------------------------------
SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND COLUMN_NAME = 'course_id'
        ),
        'SELECT 1',
        'ALTER TABLE user_quiz_attempts ADD COLUMN course_id BIGINT UNSIGNED NULL AFTER user_id'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND COLUMN_NAME = 'lesson_id'
        )
        AND EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND COLUMN_NAME = 'course_id'
        ),
        'UPDATE user_quiz_attempts a INNER JOIN lessons l ON l.id = a.lesson_id SET a.course_id = l.course_id WHERE a.id IS NOT NULL AND a.course_id IS NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND CONSTRAINT_NAME = 'fk_user_quiz_attempt_lesson'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        'ALTER TABLE user_quiz_attempts DROP FOREIGN KEY fk_user_quiz_attempt_lesson',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND INDEX_NAME = 'uk_user_lesson_attempt'
        ),
        'ALTER TABLE user_quiz_attempts DROP INDEX uk_user_lesson_attempt',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND INDEX_NAME = 'idx_quiz_attempt_lesson'
        ),
        'ALTER TABLE user_quiz_attempts DROP INDEX idx_quiz_attempt_lesson',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND INDEX_NAME = 'idx_quiz_attempt_passed'
        ),
        'ALTER TABLE user_quiz_attempts DROP INDEX idx_quiz_attempt_passed',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND COLUMN_NAME = 'course_id'
              AND IS_NULLABLE = 'YES'
        ),
        'ALTER TABLE user_quiz_attempts MODIFY COLUMN course_id BIGINT UNSIGNED NOT NULL',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND INDEX_NAME = 'uk_user_course_attempt'
        ),
        'SELECT 1',
        'ALTER TABLE user_quiz_attempts ADD UNIQUE KEY uk_user_course_attempt (user_id, course_id, attempt_number)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND INDEX_NAME = 'idx_quiz_attempt_course'
        ),
        'SELECT 1',
        'ALTER TABLE user_quiz_attempts ADD KEY idx_quiz_attempt_course (course_id)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND INDEX_NAME = 'idx_quiz_attempt_passed'
        ),
        'SELECT 1',
        'ALTER TABLE user_quiz_attempts ADD KEY idx_quiz_attempt_passed (user_id, course_id, passed)'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND CONSTRAINT_NAME = 'fk_user_quiz_attempt_course'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ),
        'SELECT 1',
        'ALTER TABLE user_quiz_attempts ADD CONSTRAINT fk_user_quiz_attempt_course FOREIGN KEY (course_id) REFERENCES courses(id) ON UPDATE CASCADE ON DELETE CASCADE'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        EXISTS(
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'user_quiz_attempts'
              AND COLUMN_NAME = 'lesson_id'
        ),
        'ALTER TABLE user_quiz_attempts DROP COLUMN lesson_id',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

COMMIT;

SET SQL_SAFE_UPDATES = @OLD_SQL_SAFE_UPDATES;

-- ------------------------------------------------------------
-- Validaciones recomendadas post-migracion
-- ------------------------------------------------------------
-- 1) Verificar que no existan quizzes sin curso
-- SELECT COUNT(*) AS quizzes_sin_curso FROM quizzes WHERE course_id IS NULL;
--
-- 2) Verificar intentos sin curso
-- SELECT COUNT(*) AS intentos_sin_curso FROM user_quiz_attempts WHERE course_id IS NULL;
--
-- 3) Verificar configuracion de 10 preguntas por curso
-- SELECT course_id, COUNT(*) AS total_preguntas
-- FROM quizzes
-- WHERE status = 1
-- GROUP BY course_id
-- HAVING COUNT(*) <> 10;
