<?php

class LearningModel
{
    private const QUIZ_PASSING_SCORE = 70.00;

    /** @var \DB\SQL */
    private $db;

    public function __construct()
    {
        $this->db = \Base::instance()->get('DB');
    }

    public function calculateLearningLevel(int $xp): array
    {
        if ($xp >= 2500) {
            return [
                'level' => 'Experto',
                'level_code' => 'EXPERTO',
                'current_xp' => $xp,
                'next_level_xp' => null
            ];
        }

        if ($xp >= 1000) {
            return [
                'level' => 'Avanzado',
                'level_code' => 'AVANZADO',
                'current_xp' => $xp,
                'next_level_xp' => 2500
            ];
        }

        if ($xp >= 300) {
            return [
                'level' => 'Intermedio',
                'level_code' => 'INTERMEDIO',
                'current_xp' => $xp,
                'next_level_xp' => 1000
            ];
        }

        return [
            'level' => 'Principiante',
            'level_code' => 'PRINCIPIANTE',
            'current_xp' => $xp,
            'next_level_xp' => 300
        ];
    }

    public function getHomeData(int $userId): array
    {
        $stats = $this->getUserStatsSummary($userId);
        $featuredCourse = $this->getFeaturedCourse($userId);
        $courseRows = $this->getCourses($userId, []);
        $learningPaths = [];

        foreach ($courseRows as $courseRow) {
            $learningPaths[] = [
                'id' => $courseRow['id'],
                'title' => $courseRow['title'],
                'description' => $courseRow['description'],
                'lessons' => $courseRow['total_lessons'],
                'progress' => $courseRow['progress_percent']
            ];
        }

        return [
            'user_progress' => [
                'level' => $stats['level'],
                'xp' => $stats['total_xp'],
                'next_level_xp' => $stats['next_level_xp'],
                'streak' => $stats['current_streak']
            ],
            'featured_course' => $featuredCourse,
            'categories' => $this->getCategories(),
            'learning_paths' => $learningPaths,
            'recommended_lessons' => $this->getRecommendations($userId)
        ];
    }

    public function getCategories(): array
    {
        $rows = $this->db->exec(
            "
            SELECT
                id,
                name,
                description,
                icon
            FROM learning_categories
            WHERE status = 1
            ORDER BY sort_order ASC, id ASC
            "
        );

        return is_array($rows) ? $rows : [];
    }

    public function getCourses(int $userId, array $filters): array
    {
        $where = ['c.status = 1'];
        $params = [$userId, $userId];

        if (!empty($filters['category_id'])) {
            $where[] = 'c.category_id = ?';
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['level'])) {
            $where[] = 'c.level = ?';
            $params[] = strtoupper((string)$filters['level']);
        }

        $sql = "
            SELECT
                c.id,
                c.title,
                c.description,
                c.image AS image_url,
                c.level,
                c.estimated_minutes,
                COUNT(DISTINCT l.id) AS total_lessons,
                COALESCE(ucp.completed_lessons, 0) AS completed_lessons,
                COALESCE(ucp.progress_percent, 0) AS progress_percent,
                c.is_featured
            FROM courses c
            INNER JOIN learning_categories lc
                ON lc.id = c.category_id
                AND lc.status = 1
            LEFT JOIN lessons l
                ON l.course_id = c.id
                AND l.status = 1
            LEFT JOIN user_course_progress ucp
                ON ucp.course_id = c.id
                AND ucp.user_id = ?
            LEFT JOIN user_learning_stats uls
                ON uls.user_id = ?
            WHERE " . implode(' AND ', $where) . "
            GROUP BY
                c.id,
                c.title,
                c.description,
                c.image,
                c.level,
                c.estimated_minutes,
                ucp.completed_lessons,
                ucp.progress_percent,
                c.is_featured
            ORDER BY c.is_featured DESC, c.sort_order ASC, c.id ASC
        ";

        $rows = $this->db->exec($sql, $params);
        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['estimated_minutes'] = (int)$row['estimated_minutes'];
            $row['total_lessons'] = (int)$row['total_lessons'];
            $row['completed_lessons'] = (int)$row['completed_lessons'];
            $row['progress_percent'] = (float)$row['progress_percent'];
            $row['is_featured'] = (int)$row['is_featured'];
        }

        return $rows;
    }

    public function getCourseDetail(int $userId, int $courseId): ?array
    {
        $course = $this->getCourseRow($courseId, $userId);
        if ($course === null) {
            return null;
        }

        $lessonRows = $this->db->exec(
            "
            SELECT
                l.id,
                l.lesson_number,
                l.title,
                l.estimated_minutes,
                COALESCE(ulp.status, 'NOT_STARTED') AS lesson_status
            FROM lessons l
            LEFT JOIN user_lesson_progress ulp
                ON ulp.lesson_id = l.id
                AND ulp.user_id = ?
            WHERE l.course_id = ?
                AND l.status = 1
            ORDER BY l.sort_order ASC, l.lesson_number ASC, l.id ASC
            ",
            [$userId, $courseId]
        );

        $lessons = [];
        if (is_array($lessonRows)) {
            foreach ($lessonRows as $row) {
                $lessons[] = [
                    'id' => (int)$row['id'],
                    'number' => (int)$row['lesson_number'],
                    'title' => $row['title'],
                    'minutes' => (int)$row['estimated_minutes'],
                    'status' => $row['lesson_status']
                ];
            }
        }

        return [
            'id' => (int)$course['id'],
            'title' => $course['title'],
            'description' => $course['description'],
            'image_url' => $course['image_url'],
            'level' => $course['level'],
            'estimated_minutes' => (int)$course['estimated_minutes'],
            'progress' => [
                'completed' => (int)$course['completed_lessons'],
                'total' => (int)$course['total_lessons'],
                'percentage' => (float)$course['progress_percent']
            ],
            'lessons' => $lessons
        ];
    }

    public function getLessonDetail(int $userId, int $lessonId): ?array
    {
        $lesson = $this->getLessonRow($lessonId, $userId);
        if ($lesson === null) {
            return null;
        }

        $contents = $this->db->exec(
            "
            SELECT
                id,
                content_type,
                title,
                content,
                image AS image_url,
                sort_order
            FROM lesson_contents
            WHERE lesson_id = ?
                AND status = 1
            ORDER BY sort_order ASC, id ASC
            ",
            [$lessonId]
        );

        $contentItems = [];
        if (is_array($contents)) {
            foreach ($contents as $row) {
                $contentItems[] = [
                    'id' => (int)$row['id'],
                    'type' => $row['content_type'],
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'image_url' => $row['image_url'],
                    'sort_order' => (int)$row['sort_order']
                ];
            }
        }

        $previousLesson = $this->getAdjacentLesson((int)$lesson['course_id'], (int)$lesson['lesson_number'], '<');
        $nextLesson = $this->getAdjacentLesson((int)$lesson['course_id'], (int)$lesson['lesson_number'], '>');

        return [
            'id' => (int)$lesson['id'],
            'course_id' => (int)$lesson['course_id'],
            'number' => (int)$lesson['lesson_number'],
            'total_lessons' => (int)$lesson['total_lessons'],
            'title' => $lesson['title'],
            'minutes' => (int)$lesson['estimated_minutes'],
            'level' => $lesson['level'],
            'status' => $lesson['lesson_status'],
            'contents' => $contentItems,
            'quiz' => $this->getLessonQuizStatus($userId, $lessonId),
            'previous_lesson' => $previousLesson,
            'next_lesson' => $nextLesson
        ];
    }

    public function startLesson(int $userId, int $lessonId): ?array
    {
        $lesson = $this->getLessonRow($lessonId, $userId);
        if ($lesson === null) {
            return null;
        }

        $courseId = (int)$lesson['course_id'];
        $this->ensureUserStatsExists($userId);
        $this->ensureCourseProgressExists($userId, $courseId, (int)$lesson['total_lessons']);

        $progressRows = $this->db->exec(
            'SELECT id, status, started_at FROM user_lesson_progress WHERE user_id = ? AND lesson_id = ?',
            [$userId, $lessonId]
        );
        $progress = is_array($progressRows) && isset($progressRows[0]) ? $progressRows[0] : null;

        if ($progress === null) {
            $this->db->exec(
                '
                INSERT INTO user_lesson_progress (
                    user_id,
                    lesson_id,
                    status,
                    progress_percent,
                    started_at,
                    completed_at,
                    xp_earned
                ) VALUES (?, ?, ?, ?, NOW(), NULL, 0)
                ',
                [$userId, $lessonId, 'IN_PROGRESS', 0]
            );
        } elseif ($progress['status'] === 'NOT_STARTED') {
            $this->db->exec(
                '
                UPDATE user_lesson_progress
                SET status = ?, progress_percent = ?, started_at = COALESCE(started_at, NOW())
                WHERE id = ?
                ',
                ['IN_PROGRESS', 0, (int)$progress['id']]
            );
        }

        $this->refreshCourseProgress($userId, $courseId, (int)$lesson['total_lessons']);

        return [
            'lesson_id' => $lessonId,
            'status' => $progress !== null && $progress['status'] === 'COMPLETED'
                ? 'COMPLETED'
                : 'IN_PROGRESS'
        ];
    }

    public function completeLesson(int $userId, int $lessonId): ?array
    {
        $lesson = $this->getLessonRow($lessonId, $userId);
        if ($lesson === null) {
            return null;
        }

        $courseId = (int)$lesson['course_id'];
        $totalLessons = (int)$lesson['total_lessons'];

        if ($this->lessonHasQuiz($lessonId) && !$this->hasPassedLessonQuiz($userId, $lessonId)) {
            return [
                'completion_blocked' => 'QUIZ_NOT_PASSED',
                'lesson_id' => $lessonId,
                'passing_score' => self::QUIZ_PASSING_SCORE
            ];
        }

        $this->db->begin();

        try {
            $this->ensureUserStatsExists($userId, true);
            $this->ensureCourseProgressExists($userId, $courseId, $totalLessons, true);

            $progressRows = $this->db->exec(
                'SELECT id, status FROM user_lesson_progress WHERE user_id = ? AND lesson_id = ? FOR UPDATE',
                [$userId, $lessonId]
            );
            $progress = is_array($progressRows) && isset($progressRows[0]) ? $progressRows[0] : null;

            $xpEarned = 0;
            if ($progress !== null && $progress['status'] === 'COMPLETED') {
                $courseProgress = $this->refreshCourseProgress($userId, $courseId, $totalLessons, true);
                $stats = $this->getLockedStatsRow($userId);
                $levelInfo = $this->calculateLearningLevel((int)$stats['total_xp']);
                $nextLesson = $this->getNextPendingLesson($userId, $courseId, (int)$lesson['lesson_number']);

                $this->db->commit();

                return [
                    'xp_earned' => 0,
                    'total_xp' => (int)$stats['total_xp'],
                    'level' => $levelInfo['level'],
                    'streak' => (int)$stats['current_streak'],
                    'course_progress' => $courseProgress,
                    'next_lesson' => $nextLesson
                ];
            }

            if ($progress === null) {
                $this->db->exec(
                    '
                    INSERT INTO user_lesson_progress (
                        user_id,
                        lesson_id,
                        status,
                        progress_percent,
                        started_at,
                        completed_at,
                        xp_earned
                    ) VALUES (?, ?, ?, ?, NOW(), NOW(), ?)
                    ',
                    [$userId, $lessonId, 'COMPLETED', 100, (int)$lesson['xp_reward']]
                );
            } else {
                $this->db->exec(
                    '
                    UPDATE user_lesson_progress
                    SET
                        status = ?,
                        progress_percent = ?,
                        started_at = COALESCE(started_at, NOW()),
                        completed_at = NOW(),
                        xp_earned = ?
                    WHERE id = ?
                    ',
                    ['COMPLETED', 100, (int)$lesson['xp_reward'], (int)$progress['id']]
                );
            }

            $xpEarned = (int)$lesson['xp_reward'];
            $statsBefore = $this->getLockedStatsRow($userId);
            $updatedStats = $this->applyLearningStatsUpdate($userId, $statsBefore, $xpEarned);

            $courseProgress = $this->refreshCourseProgress($userId, $courseId, $totalLessons, true);
            $nextLesson = $this->getNextPendingLesson($userId, $courseId, (int)$lesson['lesson_number']);

            $this->db->commit();

            return [
                'xp_earned' => $xpEarned,
                'total_xp' => $updatedStats['total_xp'],
                'level' => $updatedStats['level'],
                'streak' => $updatedStats['current_streak'],
                'course_progress' => $courseProgress,
                'next_lesson' => $nextLesson
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    public function getLessonQuiz(int $userId, int $lessonId): ?array
    {
        $lesson = $this->getLessonRow($lessonId, $userId);
        if ($lesson === null) {
            return null;
        }

        $questionRows = $this->db->exec(
            "
            SELECT
                q.id,
                q.question,
                q.sort_order
            FROM quizzes q
            WHERE q.lesson_id = ?
                AND q.status = 1
            ORDER BY q.sort_order ASC, q.id ASC
            ",
            [$lessonId]
        );

        if (!is_array($questionRows) || empty($questionRows)) {
            return [
                'lesson_id' => $lessonId,
                'passing_score' => self::QUIZ_PASSING_SCORE,
                'passed' => false,
                'has_quiz' => false,
                'questions' => []
            ];
        }

        $questions = [];

        foreach ($questionRows as $questionRow) {
            $quizId = (int)$questionRow['id'];

            $optionRows = $this->db->exec(
                "
                SELECT
                    id,
                    option_text,
                    sort_order
                FROM quiz_options
                WHERE quiz_id = ?
                ORDER BY sort_order ASC, id ASC
                ",
                [$quizId]
            );

            $options = [];

            if (is_array($optionRows)) {
                foreach ($optionRows as $optionRow) {
                    $options[] = [
                        'option_id' => (int)$optionRow['id'],
                        'text' => $optionRow['option_text']
                    ];
                }
            }

            $questions[] = [
                'quiz_id' => $quizId,
                'question' => $questionRow['question'],
                'options' => $options
            ];
        }

        return [
            'lesson_id' => $lessonId,
            'passing_score' => self::QUIZ_PASSING_SCORE,
            'passed' => $this->hasPassedLessonQuiz($userId, $lessonId),
            'has_quiz' => true,
            'questions' => $questions
        ];
    }

    public function submitLessonQuiz(int $userId, int $lessonId, array $answers): ?array
    {
        $lesson = $this->getLessonRow($lessonId, $userId);
        if ($lesson === null) {
            return null;
        }

        $questionRows = $this->db->exec(
            "
            SELECT
                q.id,
                q.question,
                q.explanation
            FROM quizzes q
            WHERE q.lesson_id = ?
                AND q.status = 1
            ORDER BY q.sort_order ASC, q.id ASC
            ",
            [$lessonId]
        );

        if (!is_array($questionRows) || empty($questionRows)) {
            return [
                'validation_error' => 'LESSON_WITHOUT_QUIZ',
                'message' => 'La lección no tiene un quiz activo'
            ];
        }

        $questionsById = [];
        foreach ($questionRows as $questionRow) {
            $questionsById[(int)$questionRow['id']] = $questionRow;
        }

        $normalizedAnswers = [];
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                return [
                    'validation_error' => 'INVALID_ANSWERS',
                    'message' => 'Cada respuesta debe ser un objeto JSON'
                ];
            }

            $quizId = $answer['quiz_id'] ?? null;
            $optionId = $answer['option_id'] ?? null;

            if (!$this->isPositiveIntegerValue($quizId) || !$this->isPositiveIntegerValue($optionId)) {
                return [
                    'validation_error' => 'INVALID_ANSWERS',
                    'message' => 'quiz_id y option_id deben ser enteros positivos'
                ];
            }

            $quizId = (int)$quizId;
            $optionId = (int)$optionId;

            if (!isset($questionsById[$quizId])) {
                return [
                    'validation_error' => 'QUESTION_NOT_IN_LESSON',
                    'message' => 'Una de las preguntas no pertenece a esta lección'
                ];
            }

            if (isset($normalizedAnswers[$quizId])) {
                return [
                    'validation_error' => 'DUPLICATE_ANSWER',
                    'message' => 'No se puede responder dos veces la misma pregunta'
                ];
            }

            $normalizedAnswers[$quizId] = $optionId;
        }

        if (count($normalizedAnswers) !== count($questionsById)) {
            return [
                'validation_error' => 'INCOMPLETE_QUIZ',
                'message' => 'Debes responder todas las preguntas del quiz',
                'answered_questions' => count($normalizedAnswers),
                'total_questions' => count($questionsById)
            ];
        }

        $this->db->begin();

        try {
            $attemptRows = $this->db->exec(
                '
                SELECT COALESCE(MAX(attempt_number), 0) AS last_attempt
                FROM user_quiz_attempts
                WHERE user_id = ? AND lesson_id = ?
                FOR UPDATE
                ',
                [$userId, $lessonId]
            );

            $attemptNumber = (int)($attemptRows[0]['last_attempt'] ?? 0) + 1;
            $totalQuestions = count($questionsById);
            $correctAnswers = 0;
            $review = [];

            foreach ($questionsById as $quizId => $questionRow) {
                $selectedOptionId = $normalizedAnswers[$quizId];

                $optionRows = $this->db->exec(
                    "
                    SELECT
                        id,
                        option_text,
                        is_correct
                    FROM quiz_options
                    WHERE quiz_id = ?
                    ORDER BY sort_order ASC, id ASC
                    ",
                    [$quizId]
                );

                $selectedOption = null;
                $correctOption = null;

                if (is_array($optionRows)) {
                    foreach ($optionRows as $optionRow) {
                        if ((int)$optionRow['id'] === $selectedOptionId) {
                            $selectedOption = $optionRow;
                        }

                        if ((int)$optionRow['is_correct'] === 1) {
                            $correctOption = $optionRow;
                        }
                    }
                }

                if ($selectedOption === null) {
                    $this->db->rollback();

                    return [
                        'validation_error' => 'OPTION_NOT_IN_QUESTION',
                        'message' => 'Una de las opciones seleccionadas no pertenece a su pregunta'
                    ];
                }

                if ($correctOption === null) {
                    throw new \RuntimeException(
                        'La pregunta ' . $quizId . ' no tiene una opción correcta configurada'
                    );
                }

                $isCorrect = (int)$selectedOption['is_correct'] === 1;

                if ($isCorrect) {
                    $correctAnswers++;
                }

                $review[] = [
                    'quiz_id' => $quizId,
                    'question' => $questionRow['question'],
                    'selected_option_id' => $selectedOptionId,
                    'selected_option_text' => $selectedOption['option_text'],
                    'correct' => $isCorrect,
                    'correct_option_id' => (int)$correctOption['id'],
                    'correct_option_text' => $correctOption['option_text'],
                    'explanation' => $questionRow['explanation']
                ];
            }

            $score = round(($correctAnswers / $totalQuestions) * 100, 2);
            $passed = $score >= self::QUIZ_PASSING_SCORE;

            $this->db->exec(
                '
                INSERT INTO user_quiz_attempts (
                    user_id,
                    lesson_id,
                    attempt_number,
                    total_questions,
                    correct_answers,
                    score,
                    passed,
                    completed_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ',
                [
                    $userId,
                    $lessonId,
                    $attemptNumber,
                    $totalQuestions,
                    $correctAnswers,
                    $score,
                    $passed ? 1 : 0
                ]
            );

            $attemptIdRows = $this->db->exec('SELECT LAST_INSERT_ID() AS id');
            $attemptId = (int)($attemptIdRows[0]['id'] ?? 0);

            foreach ($review as $item) {
                $this->db->exec(
                    '
                    INSERT INTO user_quiz_answers (
                        attempt_id,
                        quiz_id,
                        selected_option_id,
                        is_correct
                    ) VALUES (?, ?, ?, ?)
                    ',
                    [
                        $attemptId,
                        (int)$item['quiz_id'],
                        (int)$item['selected_option_id'],
                        $item['correct'] ? 1 : 0
                    ]
                );
            }

            $this->db->commit();

            return [
                'attempt_id' => $attemptId,
                'attempt_number' => $attemptNumber,
                'lesson_id' => $lessonId,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctAnswers,
                'score' => $score,
                'passing_score' => self::QUIZ_PASSING_SCORE,
                'passed' => $passed,
                'can_complete_lesson' => $passed,
                'review' => $review
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getLessonQuizStatus(int $userId, int $lessonId): array
    {
        return [
            'available' => $this->lessonHasQuiz($lessonId),
            'passed' => $this->hasPassedLessonQuiz($userId, $lessonId),
            'passing_score' => self::QUIZ_PASSING_SCORE
        ];
    }

    public function getRecommendations(int $userId): array
    {
        $startedRecommendation = $this->db->exec(
            "
            SELECT
                l.id,
                l.course_id,
                l.lesson_number,
                l.title,
                l.estimated_minutes,
                c.title AS course_title,
                c.level,
                c.is_featured
            FROM user_course_progress ucp
            INNER JOIN courses c
                ON c.id = ucp.course_id
                AND c.status = 1
            INNER JOIN lessons l
                ON l.course_id = c.id
                AND l.status = 1
            LEFT JOIN user_lesson_progress ulp
                ON ulp.lesson_id = l.id
                AND ulp.user_id = ?
            WHERE ucp.user_id = ?
                AND ucp.status IN ('IN_PROGRESS', 'NOT_STARTED')
                AND COALESCE(ulp.status, 'NOT_STARTED') <> 'COMPLETED'
            ORDER BY c.is_featured DESC, ucp.updated_at DESC, l.sort_order ASC, l.lesson_number ASC
            LIMIT 3
            ",
            [$userId, $userId]
        );

        if (is_array($startedRecommendation) && !empty($startedRecommendation)) {
            return $this->formatRecommendedLessons($startedRecommendation);
        }

        $basicRecommendation = $this->db->exec(
            "
            SELECT
                l.id,
                l.course_id,
                l.lesson_number,
                l.title,
                l.estimated_minutes,
                c.title AS course_title,
                c.level,
                c.is_featured
            FROM courses c
            INNER JOIN lessons l
                ON l.course_id = c.id
                AND l.status = 1
            LEFT JOIN user_lesson_progress ulp
                ON ulp.lesson_id = l.id
                AND ulp.user_id = ?
            WHERE c.status = 1
                AND c.level = 'BASICO'
                AND COALESCE(ulp.status, 'NOT_STARTED') <> 'COMPLETED'
            ORDER BY c.is_featured DESC, c.sort_order ASC, l.sort_order ASC, l.lesson_number ASC
            LIMIT 3
            ",
            [$userId]
        );

        return $this->formatRecommendedLessons(is_array($basicRecommendation) ? $basicRecommendation : []);
    }

    public function getUserStatsSummary(int $userId): array
    {
        $this->ensureUserStatsExists($userId);

        $rows = $this->db->exec(
            'SELECT total_xp, current_streak, longest_streak, completed_lessons, completed_courses FROM user_learning_stats WHERE user_id = ?',
            [$userId]
        );

        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : [
            'total_xp' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'completed_lessons' => 0,
            'completed_courses' => 0
        ];

        $levelInfo = $this->calculateLearningLevel((int)$row['total_xp']);

        return [
            'level' => $levelInfo['level'],
            'total_xp' => (int)$row['total_xp'],
            'next_level_xp' => $levelInfo['next_level_xp'],
            'current_streak' => (int)$row['current_streak'],
            'longest_streak' => (int)$row['longest_streak'],
            'completed_lessons' => (int)$row['completed_lessons'],
            'completed_courses' => (int)$row['completed_courses']
        ];
    }


    private function lessonHasQuiz(int $lessonId): bool
    {
        $rows = $this->db->exec(
            '
            SELECT COUNT(*) AS total
            FROM quizzes
            WHERE lesson_id = ?
                AND status = 1
            ',
            [$lessonId]
        );

        return (int)($rows[0]['total'] ?? 0) > 0;
    }

    private function hasPassedLessonQuiz(int $userId, int $lessonId): bool
    {
        $rows = $this->db->exec(
            '
            SELECT id
            FROM user_quiz_attempts
            WHERE user_id = ?
                AND lesson_id = ?
                AND passed = 1
            LIMIT 1
            ',
            [$userId, $lessonId]
        );

        return is_array($rows) && isset($rows[0]);
    }

    private function isPositiveIntegerValue($value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        return is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1;
    }

    private function getFeaturedCourse(int $userId): ?array
    {
        $rows = $this->db->exec(
            "
            SELECT
                c.id,
                c.title,
                c.description,
                c.image AS image_url,
                COALESCE(ucp.progress_percent, 0) AS progress
            FROM courses c
            LEFT JOIN user_course_progress ucp
                ON ucp.course_id = c.id
                AND ucp.user_id = ?
            WHERE c.status = 1
            ORDER BY c.is_featured DESC, c.sort_order ASC, c.id ASC
            LIMIT 1
            ",
            [$userId]
        );

        if (!is_array($rows) || !isset($rows[0])) {
            return null;
        }

        $row = $rows[0];

        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'image_url' => $row['image_url'],
            'progress' => (float)$row['progress']
        ];
    }

    private function getCourseRow(int $courseId, int $userId): ?array
    {
        $rows = $this->db->exec(
            "
            SELECT
                c.id,
                c.title,
                c.description,
                c.image AS image_url,
                c.level,
                c.estimated_minutes,
                COUNT(DISTINCT l.id) AS total_lessons,
                COALESCE(ucp.completed_lessons, 0) AS completed_lessons,
                COALESCE(ucp.progress_percent, 0) AS progress_percent
            FROM courses c
            LEFT JOIN lessons l
                ON l.course_id = c.id
                AND l.status = 1
            LEFT JOIN user_course_progress ucp
                ON ucp.course_id = c.id
                AND ucp.user_id = ?
            WHERE c.id = ?
                AND c.status = 1
            GROUP BY
                c.id,
                c.title,
                c.description,
                c.image,
                c.level,
                c.estimated_minutes,
                ucp.completed_lessons,
                ucp.progress_percent
            LIMIT 1
            ",
            [$userId, $courseId]
        );

        if (!is_array($rows) || !isset($rows[0])) {
            return null;
        }

        return $rows[0];
    }

    private function getLessonRow(int $lessonId, int $userId): ?array
    {
        $rows = $this->db->exec(
            "
            SELECT
                l.id,
                l.course_id,
                l.lesson_number,
                l.title,
                l.estimated_minutes,
                l.xp_reward,
                c.level,
                totals.total_lessons,
                COALESCE(ulp.status, 'NOT_STARTED') AS lesson_status
            FROM lessons l
            INNER JOIN courses c
                ON c.id = l.course_id
                AND c.status = 1
            INNER JOIN (
                SELECT course_id, COUNT(*) AS total_lessons
                FROM lessons
                WHERE status = 1
                GROUP BY course_id
            ) totals
                ON totals.course_id = l.course_id
            LEFT JOIN user_lesson_progress ulp
                ON ulp.lesson_id = l.id
                AND ulp.user_id = ?
            WHERE l.id = ?
                AND l.status = 1
            LIMIT 1
            ",
            [$userId, $lessonId]
        );

        if (!is_array($rows) || !isset($rows[0])) {
            return null;
        }

        return $rows[0];
    }

    private function getAdjacentLesson(int $courseId, int $lessonNumber, string $operator): ?array
    {
        $order = $operator === '<' ? 'DESC' : 'ASC';
        $rows = $this->db->exec(
            "
            SELECT id
            FROM lessons
            WHERE course_id = ?
                AND status = 1
                AND lesson_number $operator ?
            ORDER BY lesson_number $order, sort_order $order, id $order
            LIMIT 1
            ",
            [$courseId, $lessonNumber]
        );

        if (!is_array($rows) || !isset($rows[0])) {
            return null;
        }

        return ['id' => (int)$rows[0]['id']];
    }

    private function ensureUserStatsExists(int $userId, bool $forUpdate = false): void
    {
        $query = 'SELECT user_id FROM user_learning_stats WHERE user_id = ?';
        if ($forUpdate) {
            $query .= ' FOR UPDATE';
        }

        $rows = $this->db->exec($query, [$userId]);
        if (is_array($rows) && isset($rows[0])) {
            return;
        }

        $this->db->exec(
            '
            INSERT INTO user_learning_stats (
                user_id,
                total_xp,
                current_level,
                current_streak,
                longest_streak,
                last_activity_date,
                completed_lessons,
                completed_courses
            ) VALUES (?, 0, ?, 0, 0, NULL, 0, 0)
            ',
            [$userId, 'PRINCIPIANTE']
        );
    }

    private function ensureCourseProgressExists(int $userId, int $courseId, int $totalLessons, bool $forUpdate = false): void
    {
        $query = 'SELECT id FROM user_course_progress WHERE user_id = ? AND course_id = ?';
        if ($forUpdate) {
            $query .= ' FOR UPDATE';
        }

        $rows = $this->db->exec($query, [$userId, $courseId]);
        if (is_array($rows) && isset($rows[0])) {
            $this->db->exec(
                'UPDATE user_course_progress SET total_lessons = ? WHERE id = ?',
                [$totalLessons, (int)$rows[0]['id']]
            );
            return;
        }

        $this->db->exec(
            '
            INSERT INTO user_course_progress (
                user_id,
                course_id,
                completed_lessons,
                total_lessons,
                progress_percent,
                status,
                started_at,
                completed_at
            ) VALUES (?, ?, 0, ?, 0, ?, NOW(), NULL)
            ',
            [$userId, $courseId, $totalLessons, 'IN_PROGRESS']
        );
    }

    private function refreshCourseProgress(int $userId, int $courseId, int $totalLessons, bool $forUpdate = false): array
    {
        $progressQuery = 'SELECT id, completed_at FROM user_course_progress WHERE user_id = ? AND course_id = ?';
        if ($forUpdate) {
            $progressQuery .= ' FOR UPDATE';
        }

        $progressRows = $this->db->exec($progressQuery, [$userId, $courseId]);
        $courseProgress = is_array($progressRows) && isset($progressRows[0]) ? $progressRows[0] : null;

        if ($courseProgress === null) {
            $this->ensureCourseProgressExists($userId, $courseId, $totalLessons, $forUpdate);
            $progressRows = $this->db->exec($progressQuery, [$userId, $courseId]);
            $courseProgress = is_array($progressRows) && isset($progressRows[0]) ? $progressRows[0] : null;
        }

        $countRows = $this->db->exec(
            "
            SELECT COUNT(*) AS completed_lessons
            FROM user_lesson_progress ulp
            INNER JOIN lessons l
                ON l.id = ulp.lesson_id
                AND l.status = 1
            WHERE ulp.user_id = ?
                AND l.course_id = ?
                AND ulp.status = 'COMPLETED'
            ",
            [$userId, $courseId]
        );

        $completedLessons = (int)($countRows[0]['completed_lessons'] ?? 0);
        $percentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0.0;
        $status = 'NOT_STARTED';
        if ($completedLessons >= $totalLessons && $totalLessons > 0) {
            $status = 'COMPLETED';
        } elseif ($completedLessons > 0) {
            $status = 'IN_PROGRESS';
        } else {
            $inProgressRows = $this->db->exec(
                "
                SELECT COUNT(*) AS started_lessons
                FROM user_lesson_progress ulp
                INNER JOIN lessons l
                    ON l.id = ulp.lesson_id
                    AND l.status = 1
                WHERE ulp.user_id = ?
                    AND l.course_id = ?
                    AND ulp.status = 'IN_PROGRESS'
                ",
                [$userId, $courseId]
            );

            if ((int)($inProgressRows[0]['started_lessons'] ?? 0) > 0) {
                $status = 'IN_PROGRESS';
            }
        }

        $completedAt = $status === 'COMPLETED' ? 'NOW()' : 'NULL';
        $startedAt = $status === 'NOT_STARTED' ? 'NULL' : 'COALESCE(started_at, NOW())';

        $this->db->exec(
            "
            UPDATE user_course_progress
            SET
                completed_lessons = ?,
                total_lessons = ?,
                progress_percent = ?,
                status = ?,
                started_at = $startedAt,
                completed_at = $completedAt
            WHERE id = ?
            ",
            [$completedLessons, $totalLessons, $percentage, $status, (int)$courseProgress['id']]
        );

        if ($forUpdate && $status === 'COMPLETED' && empty($courseProgress['completed_at'])) {
            $stats = $this->getLockedStatsRow($userId);
            $this->db->exec(
                'UPDATE user_learning_stats SET completed_courses = ? WHERE user_id = ?',
                [(int)$stats['completed_courses'] + 1, $userId]
            );
        }

        return [
            'completed' => $completedLessons,
            'total' => $totalLessons,
            'percentage' => $percentage
        ];
    }

    private function getLockedStatsRow(int $userId): array
    {
        $rows = $this->db->exec(
            'SELECT total_xp, current_level, current_streak, longest_streak, last_activity_date, completed_lessons, completed_courses FROM user_learning_stats WHERE user_id = ? FOR UPDATE',
            [$userId]
        );

        return is_array($rows) && isset($rows[0]) ? $rows[0] : [
            'total_xp' => 0,
            'current_level' => 'PRINCIPIANTE',
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null,
            'completed_lessons' => 0,
            'completed_courses' => 0
        ];
    }

    private function applyLearningStatsUpdate(int $userId, array $stats, int $xpEarned): array
    {
        $totalXp = (int)$stats['total_xp'] + $xpEarned;
        $streakData = $this->calculateNextStreak(
            $stats['last_activity_date'],
            (int)$stats['current_streak'],
            (int)$stats['longest_streak']
        );
        $levelInfo = $this->calculateLearningLevel($totalXp);
        $completedLessons = (int)$stats['completed_lessons'] + 1;

        $this->db->exec(
            '
            UPDATE user_learning_stats
            SET
                total_xp = ?,
                current_level = ?,
                current_streak = ?,
                longest_streak = ?,
                last_activity_date = CURDATE(),
                completed_lessons = ?
            WHERE user_id = ?
            ',
            [
                $totalXp,
                $levelInfo['level_code'],
                $streakData['current_streak'],
                $streakData['longest_streak'],
                $completedLessons,
                $userId
            ]
        );

        return [
            'total_xp' => $totalXp,
            'level' => $levelInfo['level'],
            'current_streak' => $streakData['current_streak'],
            'longest_streak' => $streakData['longest_streak'],
            'completed_lessons' => $completedLessons
        ];
    }

    private function calculateNextStreak($lastActivityDate, int $currentStreak, int $longestStreak): array
    {
        $today = new \DateTimeImmutable('today');

        if (empty($lastActivityDate)) {
            $currentStreak = 1;
        } else {
            $last = new \DateTimeImmutable($lastActivityDate);
            $diffDays = (int)$last->diff($today)->format('%r%a');

            if ($diffDays === 0) {
                $currentStreak = max(1, $currentStreak);
            } elseif ($diffDays === 1) {
                $currentStreak = max(1, $currentStreak) + 1;
            } else {
                $currentStreak = 1;
            }
        }

        if ($currentStreak > $longestStreak) {
            $longestStreak = $currentStreak;
        }

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak
        ];
    }

    private function getNextPendingLesson(int $userId, int $courseId, int $currentLessonNumber): ?array
    {
        $rows = $this->db->exec(
            "
            SELECT
                l.id,
                l.title
            FROM lessons l
            LEFT JOIN user_lesson_progress ulp
                ON ulp.lesson_id = l.id
                AND ulp.user_id = ?
            WHERE l.course_id = ?
                AND l.status = 1
                AND l.lesson_number > ?
                AND COALESCE(ulp.status, 'NOT_STARTED') <> 'COMPLETED'
            ORDER BY l.sort_order ASC, l.lesson_number ASC, l.id ASC
            LIMIT 1
            ",
            [$userId, $courseId, $currentLessonNumber]
        );

        if (!is_array($rows) || !isset($rows[0])) {
            return null;
        }

        return [
            'id' => (int)$rows[0]['id'],
            'title' => $rows[0]['title']
        ];
    }

    private function formatRecommendedLessons(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (int)$row['id'],
                'course_id' => (int)$row['course_id'],
                'course_title' => $row['course_title'],
                'title' => $row['title'],
                'number' => (int)$row['lesson_number'],
                'minutes' => (int)$row['estimated_minutes'],
                'level' => $row['level']
            ];
        }

        return $items;
    }
}