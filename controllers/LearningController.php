<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/LearningModel.php';

class LearningController extends BaseController
{
    /** @var \LearningModel */
    protected $learningModel;

    public function __construct()
    {
        parent::__construct();
        $this->learningModel = new \LearningModel();
    }

    public function home($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $this->successResponse(
                $this->learningModel->getHomeData($userId),
                'Inicio de aprendizaje obtenido correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::home error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el inicio del módulo Aprender', 500);
        }
    }

    public function categories($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $this->successResponse(
                $this->learningModel->getCategories(),
                'Categorías de aprendizaje obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::categories error: ' . $e->getMessage());
            $this->errorResponse('No se pudieron obtener las categorías de aprendizaje', 500);
        }
    }

    /**
     * POST /learning/courses/category
     * JSON:
     * {
     *   "category_id": 1
     * }
     */
    public function coursesByCategory($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $categoryId = $data['category_id'] ?? null;

            if (!$this->isPositiveIntegerValue($categoryId)) {
                $this->errorResponse(
                    'Parámetros inválidos',
                    400,
                    ['category_id' => 'Debe ser un entero positivo']
                );
                return;
            }

            $filters = [
                'category_id' => (int)$categoryId
            ];

            $this->successResponse(
                $this->learningModel->getCourses($userId, $filters),
                'Cursos por categoría obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::coursesByCategory error: ' . $e->getMessage());
            $this->errorResponse('No se pudieron obtener los cursos por categoría', 500);
        }
    }

    /**
     * POST /learning/courses/level
     * JSON:
     * {
     *   "level": "BASICO"
     * }
     */
    public function coursesByLevel($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $level = strtoupper(trim((string)($data['level'] ?? '')));
            $allowedLevels = ['BASICO', 'INTERMEDIO', 'AVANZADO'];

            if (!in_array($level, $allowedLevels, true)) {
                $this->errorResponse(
                    'Parámetros inválidos',
                    400,
                    ['level' => 'Debe ser BASICO, INTERMEDIO o AVANZADO']
                );
                return;
            }

            $filters = [
                'level' => $level
            ];

            $this->successResponse(
                $this->learningModel->getCourses($userId, $filters),
                'Cursos por nivel obtenidos correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::coursesByLevel error: ' . $e->getMessage());
            $this->errorResponse('No se pudieron obtener los cursos por nivel', 500);
        }
    }

    /**
     * POST /learning/courses/detail
     * JSON:
     * {
     *   "course_id": 1
     * }
     */
    public function courseDetail($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $courseId = $data['course_id'] ?? null;

            if (!$this->isPositiveIntegerValue($courseId)) {
                $this->errorResponse(
                    'Parámetros inválidos',
                    400,
                    ['course_id' => 'Debe ser un entero positivo']
                );
                return;
            }

            $course = $this->learningModel->getCourseDetail(
                $userId,
                (int)$courseId
            );

            if ($course === null) {
                $this->errorResponse('Curso no encontrado', 404);
                return;
            }

            $this->successResponse(
                $course,
                'Detalle del curso obtenido correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::courseDetail error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el detalle del curso', 500);
        }
    }

    /**
     * POST /learning/lessons/detail
     * JSON:
     * {
     *   "lesson_id": 1
     * }
     */
    public function lessonDetail($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $lessonId = $data['lesson_id'] ?? null;

            if (!$this->isPositiveIntegerValue($lessonId)) {
                $this->errorResponse(
                    'Parámetros inválidos',
                    400,
                    ['lesson_id' => 'Debe ser un entero positivo']
                );
                return;
            }

            $lesson = $this->learningModel->getLessonDetail(
                $userId,
                (int)$lessonId
            );

            if ($lesson === null) {
                $this->errorResponse('Lección no encontrada', 404);
                return;
            }

            $this->successResponse(
                $lesson,
                'Lección obtenida correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::lessonDetail error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener la lección', 500);
        }
    }

    public function startLesson($f3)
    {
        $this->errorResponse(
            'Este endpoint fue deshabilitado. Al consultar el detalle de la lección, esta se inicia automáticamente.',
            410
        );
    }

    public function completeCourse($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $courseId = $data['course_id'] ?? null;

            if (!$this->isPositiveIntegerValue($courseId)) {
                $this->errorResponse(
                    'Parámetros inválidos',
                    400,
                    ['course_id' => 'Debe ser un entero positivo']
                );
                return;
            }

            $courseId = (int)$courseId;

            $result = $this->learningModel->completeCourse($userId, $courseId);
            if ($result === null) {
                $this->errorResponse('Curso no encontrado', 404);
                return;
            }

            if (($result['completion_blocked'] ?? null) === 'ALL_LESSONS_NOT_COMPLETED') {
                $this->errorResponse(
                    'Debes completar todas las lecciones del curso antes de finalizarlo',
                    409,
                    $result
                );
                return;
            }

            if (($result['completion_blocked'] ?? null) === 'QUIZ_NOT_PASSED') {
                $this->errorResponse(
                    'Debes aprobar el quiz del curso antes de completarlo',
                    409,
                    $result
                );
                return;
            }

            $this->successResponse($result, 'Curso completado con éxito');
        } catch (\Throwable $e) {
            error_log('LearningController::completeCourse error: ' . $e->getMessage());
            $this->errorResponse('No se pudo completar el curso', 500);
        }
    }

    public function completeLesson($f3)
    {
        $this->completeCourse($f3);
    }


    /**
     * POST /learning/quiz/detail
     * JSON:
     * {
    *   "course_id": 1
     * }
     */
    public function quizDetail($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $courseId = $data['course_id'] ?? null;

            if (!$this->isPositiveIntegerValue($courseId)) {
                $this->errorResponse(
                    'Parámetros inválidos',
                    400,
                    ['course_id' => 'Debe ser un entero positivo']
                );
                return;
            }

            $quiz = $this->learningModel->getCourseQuiz(
                $userId,
                (int)$courseId
            );

            if ($quiz === null) {
                $this->errorResponse('Curso no encontrado', 404);
                return;
            }

            $this->successResponse(
                $quiz,
                !empty($quiz['has_quiz'])
                    ? 'Quiz obtenido correctamente'
                    : 'El curso no tiene un quiz activo'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::quizDetail error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el quiz', 500);
        }
    }

    /**
     * POST /learning/quiz/submit
     * JSON:
     * {
    *   "course_id": 1,
     *   "answers": [
     *      {"quiz_id": 1, "option_id": 2},
     *      {"quiz_id": 2, "option_id": 5}
     *   ]
     * }
     */
    public function submitQuiz($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $data = $this->getJsonBody();
            if ($data === null) {
                return;
            }

            $courseId = $data['course_id'] ?? null;
            $answers = $data['answers'] ?? null;

            $errors = [];

            if (!$this->isPositiveIntegerValue($courseId)) {
                $errors['course_id'] = 'Debe ser un entero positivo';
            }

            if (!is_array($answers) || empty($answers)) {
                $errors['answers'] = 'Debe contener al menos una respuesta';
            }

            if (!empty($errors)) {
                $this->errorResponse('Parámetros inválidos', 400, $errors);
                return;
            }

            $result = $this->learningModel->submitCourseQuiz(
                $userId,
                (int)$courseId,
                $answers
            );

            if ($result === null) {
                $this->errorResponse('Curso no encontrado', 404);
                return;
            }

            if (isset($result['validation_error'])) {
                $this->errorResponse(
                    $result['message'] ?? 'No se pudo procesar el quiz',
                    400,
                    $result
                );
                return;
            }

            $this->successResponse(
                $result,
                !empty($result['passed'])
                    ? 'Quiz aprobado correctamente'
                    : 'Quiz enviado correctamente. No alcanzaste el puntaje mínimo'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::submitQuiz error: ' . $e->getMessage());
            $this->errorResponse('No se pudo enviar el quiz', 500);
        }
    }

    public function recommendations($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $this->successResponse(
                $this->learningModel->getRecommendations($userId),
                'Recomendaciones obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::recommendations error: ' . $e->getMessage());
            $this->errorResponse('No se pudieron obtener las recomendaciones', 500);
        }
    }

    public function stats($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $this->successResponse(
                $this->learningModel->getUserStatsSummary($userId),
                'Estadísticas de aprendizaje obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::stats error: ' . $e->getMessage());
            $this->errorResponse('No se pudieron obtener las estadísticas de aprendizaje', 500);
        }
    }

    public function faq($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $this->successResponse(
                $this->learningModel->getShortFaq(),
                'Preguntas frecuentes obtenidas correctamente'
            );
        } catch (\Throwable $e) {
            error_log('LearningController::faq error: ' . $e->getMessage());
            $this->errorResponse('No se pudieron obtener las preguntas frecuentes', 500);
        }
    }

    private function resolveAuthenticatedUserId($f3): ?int
    {
        $decoded = $this->requireAuth($f3);

        if (!$decoded || !isset($decoded->data->user_id)) {
            return null;
        }

        return (int)$decoded->data->user_id;
    }

    /**
     * Lee el body enviado como application/json.
     */
    private function getJsonBody(): ?array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            $this->errorResponse(
                'El cuerpo de la solicitud es obligatorio',
                400
            );
            return null;
        }

        $data = json_decode($rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->errorResponse(
                'El cuerpo de la solicitud debe contener un JSON válido',
                400
            );
            return null;
        }

        return $data;
    }

    private function isPositiveIntegerValue($value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value)) {
            return true;
        }

        return false;
    }
}