<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController extends BaseController
{
    /** @var \DashboardModel */
    protected $dashboardModel;

    public function __construct()
    {
        parent::__construct();
        $this->dashboardModel = new \DashboardModel();
    }

    public function home($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $userName = $this->resolveUserName($userId) ?: 'Usuario';

            $this->successResponse([
                'user' => [
                    'name' => $userName,
                    'greeting' => 'Sigue fortaleciendo tu salud financiera.'
                ],
                'balance_card' => [
                    'label' => 'Balance total',
                    'amount' => 4580.00,
                    'currency' => 'PEN',
                    'currency_symbol' => 'S/',
                    'account_name' => 'Cuenta principal',
                    'account_masked' => '**** 4321'
                ],
                'shortcuts' => [
                    ['key' => 'accounts', 'title' => 'Cuentas'],
                    ['key' => 'budgets', 'title' => 'Presupuestos'],
                    ['key' => 'transactions', 'title' => 'Transacciones'],
                    ['key' => 'learn', 'title' => 'Aprender']
                ],
                'financial_overview' => [
                    'monthly_savings' => [
                        'label' => 'Ahorro del mes',
                        'amount' => 620.00,
                        'currency_symbol' => 'S/',
                        'change_percent_vs_last_month' => 18
                    ],
                    'budget_usage' => [
                        'label' => 'Presupuesto usado',
                        'percentage' => 68,
                        'used' => 1360.00,
                        'total' => 2000.00,
                        'currency_symbol' => 'S/'
                    ],
                    'monthly_flow' => [
                        'label' => 'Flujo del mes',
                        'amount' => 1240.00,
                        'currency_symbol' => 'S/',
                        'formula' => 'Entradas - Salidas'
                    ]
                ],
                'featured_lesson' => [
                    'badge' => 'Lección destacada',
                    'title' => 'Regla 50/30/20',
                    'description' => 'Aprende a distribuir tu ingreso de forma inteligente.',
                    'progress_distribution' => [
                        'needs' => 50,
                        'wants' => 30,
                        'savings' => 20
                    ],
                    'action_label' => 'Ver lección'
                ],
                'daily_tip' => [
                    'title' => 'Pequeños hábitos, grandes resultados.',
                    'description' => 'Revisa tus gastos hormiga. Ahorrar un poco hoy puede hacer una gran diferencia mañana.'
                ]
            ], 'Home obtenido correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::home error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener la información del home', 500);
        }
    }

    public function index($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId, true);
            if ($filters === null) {
                return;
            }

            $summary = $this->dashboardModel->getSummary(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $expensesByCategory = $this->dashboardModel->getExpensesByCategory(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $incomeVsExpenses = $this->dashboardModel->getIncomeVsExpenses(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $balanceEvolution = $this->dashboardModel->getBalanceEvolution(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $budgetProgress = $this->dashboardModel->getBudgetProgress(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $topExpenseCategories = $this->dashboardModel->getTopExpenseCategories(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas'],
                $filters['limit']
            );

            $this->successResponse([
                'summary' => $summary['summary'],
                'expenses_by_category' => $expensesByCategory['items'],
                'income_vs_expenses' => $incomeVsExpenses['items'],
                'balance_evolution' => [
                    'initial_balance' => $balanceEvolution['initial_balance'],
                    'items' => $balanceEvolution['items']
                ],
                'budget_progress' => [
                    'totals' => $budgetProgress['totals'],
                    'items' => $budgetProgress['items']
                ],
                'top_expense_categories' => $topExpenseCategories['items']
            ], 'Dashboard financiero obtenido correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::index error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener la información del dashboard', 500);
        }
    }

    public function summary($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getSummary(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $this->successResponse($data, 'Resumen financiero obtenido correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::summary error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el resumen financiero', 500);
        }
    }

    public function expensesByCategory($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getExpensesByCategory(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $this->successResponse($data, 'Gastos por categoría obtenidos correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::expensesByCategory error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el bloque de gastos por categoría', 500);
        }
    }

    public function incomeVsExpenses($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getIncomeVsExpenses(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $this->successResponse($data, 'Comparación de ingresos y gastos obtenida correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::incomeVsExpenses error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener la comparación de ingresos y gastos', 500);
        }
    }

    public function balanceEvolution($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getBalanceEvolution(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $this->successResponse($data, 'Evolución del saldo obtenida correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::balanceEvolution error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener la evolución del saldo', 500);
        }
    }

    public function budgetProgress($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getBudgetProgress(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas']
            );

            $this->successResponse($data, 'Progreso de presupuestos obtenido correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::budgetProgress error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el progreso de presupuestos', 500);
        }
    }

    public function topExpenseCategories($f3)
    {
        try {
            $userId = $this->resolveAuthenticatedUserId($f3);
            if ($userId === null) {
                return;
            }

            $filters = $this->parseDashboardFilters($f3, $userId, true);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getTopExpenseCategories(
                $userId,
                $filters['fecha_inicio'],
                $filters['fecha_fin'],
                $filters['cuentas'],
                $filters['limit']
            );

            $this->successResponse($data, 'Categorías con mayores gastos obtenidas correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::topExpenseCategories error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el top de categorías con mayores gastos', 500);
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

    private function parseDashboardFilters($f3, int $userId, bool $withLimit = false): ?array
    {
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return null;
        }

        $errors = [];

        $fechaInicio = $body['fecha_inicio'] ?? null;
        $fechaFin = $body['fecha_fin'] ?? null;

        if (!$this->isValidDateString($fechaInicio)) {
            $errors['fecha_inicio'] = 'fecha_inicio es obligatoria y debe usar el formato YYYY-MM-DD';
        }

        if (!$this->isValidDateString($fechaFin)) {
            $errors['fecha_fin'] = 'fecha_fin es obligatoria y debe usar el formato YYYY-MM-DD';
        }

        if (
            !isset($errors['fecha_inicio'])
            && !isset($errors['fecha_fin'])
            && strtotime($fechaFin) < strtotime($fechaInicio)
        ) {
            $errors['fecha_fin'] = 'fecha_fin no puede ser menor que fecha_inicio';
        }

        if (!array_key_exists('cuentas', $body) || !is_array($body['cuentas'])) {
            $errors['cuentas'] = 'cuentas debe ser un arreglo de IDs enteros positivos';
        }

        $accountIds = [];
        if (!isset($errors['cuentas'])) {
            foreach ($body['cuentas'] as $accountId) {
                if (!$this->isPositiveIntegerValue($accountId)) {
                    $errors['cuentas'] = 'cuentas solo puede contener IDs enteros positivos';
                    break;
                }

                $accountIds[] = (int)$accountId;
            }

            if (!isset($errors['cuentas'])) {
                $accountIds = array_values(array_unique($accountIds));
            }
        }

        $limit = 5;
        if ($withLimit && array_key_exists('limit', $body)) {
            if (!$this->isPositiveIntegerValue($body['limit'])) {
                $errors['limit'] = 'limit debe ser un entero entre 1 y 10';
            } else {
                $limit = (int)$body['limit'];
            }
        }

        if ($withLimit && ($limit < 1 || $limit > 10)) {
            $errors['limit'] = 'limit debe ser un entero entre 1 y 10';
        }

        if (!empty($accountIds)) {
            $validAccountIds = $this->dashboardModel->validateUserAccounts($userId, $accountIds);
            if (count($validAccountIds) !== count($accountIds)) {
                $errors['cuentas'] = 'Una o más cuentas no existen o no pertenecen al usuario autenticado';
            } else {
                $accountIds = $validAccountIds;
            }
        }

        if (!empty($errors)) {
            $this->validationError($errors, 'Parámetros inválidos');
            return null;
        }

        return [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'cuentas' => $accountIds,
            'limit' => $limit
        ];
    }

    private function isValidDateString($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date instanceof \DateTime && $date->format('Y-m-d') === $value;
    }

    private function isPositiveIntegerValue($value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        if (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1) {
            return true;
        }

        return false;
    }

    private function resolveUserName(int $userId): ?string
    {
        $rows = \Base::instance()->get('DB')->exec(
            'SELECT nombre FROM usuarios WHERE id = ? LIMIT 1',
            [$userId]
        );

        if (!is_array($rows) || !isset($rows[0]['nombre'])) {
            return null;
        }

        $name = trim((string)$rows[0]['nombre']);
        return $name !== '' ? $name : null;
    }
}
