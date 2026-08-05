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

    public function index($f3)
    {
        try {
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, true, true);
            if ($filters === null) {
                return;
            }

            $userId = (int)$decoded->data->user_id;

            $summary = $this->dashboardModel->getSummary(
                $userId,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date']
            );

            $expensesByCategory = $this->dashboardModel->getExpensesByCategory(
                $userId,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date']
            );

            $incomeVsExpenses = $this->dashboardModel->getIncomeVsExpenses(
                $userId,
                $filters['year']
            );

            $balanceEvolution = $this->dashboardModel->getBalanceEvolution(
                $userId,
                $filters['year']
            );

            $budgetProgress = $this->dashboardModel->getBudgetProgress(
                $userId,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date']
            );

            $topExpenseCategories = $this->dashboardModel->getTopExpenseCategories(
                $userId,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date'],
                $filters['limit']
            );

            $this->successResponse([
                'filters' => [
                    'year' => $filters['year'],
                    'month' => $filters['month'],
                    'start_date' => $filters['start_date'],
                    'end_date' => $filters['end_date']
                ],
                'summary' => $summary['summary'],
                'expenses_by_category' => $expensesByCategory['items'],
                'income_vs_expenses' => $incomeVsExpenses['items'],
                'balance_evolution' => $balanceEvolution['items'],
                'budget_progress' => $budgetProgress['items'],
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
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, true, false);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getSummary(
                (int)$decoded->data->user_id,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date']
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
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, true, false);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getExpensesByCategory(
                (int)$decoded->data->user_id,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date']
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
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, false, false);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getIncomeVsExpenses(
                (int)$decoded->data->user_id,
                $filters['year']
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
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, false, false);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getBalanceEvolution(
                (int)$decoded->data->user_id,
                $filters['year']
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
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, true, false);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getBudgetProgress(
                (int)$decoded->data->user_id,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date']
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
            $decoded = $this->requireAuth($f3);
            if (!$decoded || !isset($decoded->data->user_id)) {
                return;
            }

            $filters = $this->parseFilters($f3, true, true);
            if ($filters === null) {
                return;
            }

            $data = $this->dashboardModel->getTopExpenseCategories(
                (int)$decoded->data->user_id,
                $filters['year'],
                $filters['month'],
                $filters['start_date'],
                $filters['end_date'],
                $filters['limit']
            );

            $this->successResponse($data, 'Categorías con mayores gastos obtenidas correctamente');
        } catch (\Throwable $e) {
            error_log('DashboardController::topExpenseCategories error: ' . $e->getMessage());
            $this->errorResponse('No se pudo obtener el top de categorías con mayores gastos', 500);
        }
    }

    private function parseFilters($f3, bool $withMonth, bool $withLimit): ?array
    {
        $now = new \DateTime('now');

        $yearRaw = $f3->get('GET.year');
        $monthRaw = $f3->get('GET.month');
        $limitRaw = $f3->get('GET.limit');

        $year = $this->toIntOrDefault($yearRaw, (int)$now->format('Y'));
        if ($year < 2000 || $year > 2100) {
            $this->validationError([
                'year' => 'El parámetro year debe estar entre 2000 y 2100'
            ], 'Parámetros inválidos');
            return null;
        }

        $month = null;
        $startDate = null;
        $endDate = null;

        if ($withMonth) {
            $month = $this->toIntOrDefault($monthRaw, (int)$now->format('n'));
            if ($month < 1 || $month > 12) {
                $this->validationError([
                    'month' => 'El parámetro month debe estar entre 1 y 12'
                ], 'Parámetros inválidos');
                return null;
            }

            $range = $this->resolveMonthDateRange($year, $month);
            $startDate = $range['start_date'];
            $endDate = $range['end_date'];
        }

        $limit = 5;
        if ($withLimit) {
            $limit = $this->toIntOrDefault($limitRaw, 5);
            if ($limit < 1 || $limit > 10) {
                $this->validationError([
                    'limit' => 'El parámetro limit debe estar entre 1 y 10'
                ], 'Parámetros inválidos');
                return null;
            }
        }

        return [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'limit' => $limit
        ];
    }

    private function resolveMonthDateRange(int $year, int $month): array
    {
        $date = \DateTime::createFromFormat('Y-n-j', $year . '-' . $month . '-1');

        return [
            'start_date' => $date->format('Y-m-01'),
            'end_date' => $date->format('Y-m-t')
        ];
    }

    private function toIntOrDefault($value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_numeric($value)) {
            return PHP_INT_MIN;
        }

        return (int)$value;
    }
}
