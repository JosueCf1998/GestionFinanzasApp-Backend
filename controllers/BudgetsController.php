<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class BudgetsController extends BaseController
{
    protected $budgetModel;
    protected $transactionDateColumn;

    public function __construct()
    {
        parent::__construct();
        $this->budgetModel = new \m_budgets();
        $this->transactionDateColumn = null;
    }

    // Wrapper to match routes.ini (POST /budgets/register)
    public function register($f3)
    {
        return $this->create($f3);
    }

    public function create($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $validation = $this->validateBudgetPayload($body, false);
        if ($validation !== true) {
            return;
        }

        $resolvedEndDate = $this->resolveEndDate(
            $body['period'],
            $body['start_date'],
            $body['end_date'] ?? null
        );

        if ($resolvedEndDate === null) {
            $this->errorResponse('Invalid period/end_date combination', 400);
            return;
        }

        $this->budgetModel->set('user_id', $decoded->data->user_id);
        $this->budgetModel->set('category_id', $body['category_id'] ?? null);
        $this->budgetModel->set('name', trim($body['name']));
        $this->budgetModel->set('amount', $body['amount']);
        $this->budgetModel->set('period', strtolower($body['period']));
        $this->budgetModel->set('start_date', $body['start_date']);
        $this->budgetModel->set('end_date', $resolvedEndDate);
        $this->budgetModel->set('status', strtolower($body['status'] ?? 'active'));
        $this->budgetModel->set('notes', $body['notes'] ?? null);

        if ($this->budgetModel->save()) {
            $this->successResponse([
                'message' => 'Budget created successfully',
                'info' => [
                    'id' => $this->budgetModel->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('Could not create budget', 500);
        }
    }

    public function update($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $budgetId = $body['budget_id'] ?? $body['id'] ?? null;
        if (!$budgetId) {
            $this->errorResponse('Budget id is required', 400);
            return;
        }

        $this->budgetModel->load(['id = ? AND user_id = ?', $budgetId, $decoded->data->user_id]);
        if (!$this->budgetModel->loaded()) {
            $this->errorResponse('Budget not found or not accessible', 404);
            return;
        }

        $current = $this->budgetModel->cast();

        $payload = [
            'name' => $body['name'] ?? $current['name'],
            'amount' => $body['amount'] ?? $current['amount'],
            'period' => $body['period'] ?? $current['period'],
            'start_date' => $body['start_date'] ?? $current['start_date'],
            'end_date' => array_key_exists('end_date', $body) ? $body['end_date'] : $current['end_date'],
            'status' => $body['status'] ?? $current['status'],
            'category_id' => array_key_exists('category_id', $body) ? $body['category_id'] : $current['category_id'],
            'notes' => array_key_exists('notes', $body) ? $body['notes'] : $current['notes']
        ];

        $validation = $this->validateBudgetPayload($payload, true);
        if ($validation !== true) {
            return;
        }

        $resolvedEndDate = $this->resolveEndDate(
            $payload['period'],
            $payload['start_date'],
            $payload['end_date']
        );

        if ($resolvedEndDate === null) {
            $this->errorResponse('Invalid period/end_date combination', 400);
            return;
        }

        $this->budgetModel->set('category_id', $payload['category_id']);
        $this->budgetModel->set('name', trim($payload['name']));
        $this->budgetModel->set('amount', $payload['amount']);
        $this->budgetModel->set('period', strtolower($payload['period']));
        $this->budgetModel->set('start_date', $payload['start_date']);
        $this->budgetModel->set('end_date', $resolvedEndDate);
        $this->budgetModel->set('status', strtolower($payload['status']));
        $this->budgetModel->set('notes', $payload['notes']);
        $this->budgetModel->save();

        $this->successResponse([
            'message' => 'Budget updated successfully',
            'info' => ['id' => $this->budgetModel->get('id')]
        ]);
    }

    public function delete($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $budgetId = $body['budget_id'] ?? $body['id'] ?? null;
        if (!$budgetId) {
            $this->errorResponse('Budget id is required', 400);
            return;
        }

        $this->budgetModel->load(['id = ? AND user_id = ?', $budgetId, $decoded->data->user_id]);

        if ($this->budgetModel->loaded()) {
            $this->budgetModel->erase();
            $this->successResponse([
                'message' => 'Budget deleted successfully',
                'info' => ['id' => (int)$budgetId]
            ]);
        } else {
            $this->errorResponse('Budget not found or not accessible', 404);
        }
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);

        $result = $this->budgetModel->find(['user_id = ?', $decoded->data->user_id]);
        $items = [];

        foreach ($result as $budget) {
            $row = $budget->cast();
            $spent = $this->calculateSpent($decoded->data->user_id, $row);
            $amount = (float)$row['amount'];
            $remaining = $amount - $spent;
            $progress = $amount > 0 ? round(($spent / $amount) * 100, 2) : 0;

            $row['amount'] = $amount;
            $row['spent'] = $spent;
            $row['remaining'] = round($remaining, 2);
            $row['progress_percent'] = $progress;
            $row['is_overspent'] = $spent > $amount;

            $items[] = $row;
        }

        $this->successResponse([
            'items' => $items,
            'total' => count($items),
            'message' => count($items) > 0
                ? 'Budget list generated successfully'
                : 'No budgets found'
        ]);
    }

    public function summary($f3)
    {
        $decoded = $this->requireAuth($f3);

        $budgets = $this->budgetModel->find([
            'user_id = ? AND status = ?',
            $decoded->data->user_id,
            'active'
        ]);

        $summaryItems = [];
        $totalBudgeted = 0.0;
        $totalSpent = 0.0;

        foreach ($budgets as $budget) {
            $row = $budget->cast();
            $amount = (float)$row['amount'];
            $spent = $this->calculateSpent($decoded->data->user_id, $row);

            $totalBudgeted += $amount;
            $totalSpent += $spent;

            $summaryItems[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'budgeted' => $amount,
                'spent' => $spent,
                'remaining' => round($amount - $spent, 2),
                'progress_percent' => $amount > 0 ? round(($spent / $amount) * 100, 2) : 0,
                'is_overspent' => $spent > $amount
            ];
        }

        usort($summaryItems, function ($a, $b) {
            return $b['progress_percent'] <=> $a['progress_percent'];
        });

        $this->successResponse([
            'totals' => [
                'budgeted' => round($totalBudgeted, 2),
                'spent' => round($totalSpent, 2),
                'remaining' => round($totalBudgeted - $totalSpent, 2),
                'usage_percent' => $totalBudgeted > 0
                    ? round(($totalSpent / $totalBudgeted) * 100, 2)
                    : 0
            ],
            'items' => $summaryItems,
            'total_active_budgets' => count($summaryItems)
        ]);
    }

    protected function validateBudgetPayload(array $payload, bool $isUpdate)
    {
        $required = ['name', 'amount', 'period', 'start_date'];
        foreach ($required as $field) {
            if (!$isUpdate && !array_key_exists($field, $payload)) {
                $this->errorResponse('Missing required field: ' . $field, 400);
                return false;
            }
        }

        if (!isset($payload['name']) || trim((string)$payload['name']) === '') {
            $this->errorResponse('Budget name is required', 400);
            return false;
        }

        if (!is_numeric($payload['amount']) || (float)$payload['amount'] <= 0) {
            $this->errorResponse('Amount must be a positive number', 400);
            return false;
        }

        $validPeriods = ['weekly', 'monthly', 'yearly', 'custom'];
        if (!in_array(strtolower((string)$payload['period']), $validPeriods, true)) {
            $this->errorResponse('Invalid period. Allowed values: weekly, monthly, yearly, custom', 400);
            return false;
        }

        if (!$this->isValidDate($payload['start_date'])) {
            $this->errorResponse('Invalid start_date. Expected format: YYYY-MM-DD', 400);
            return false;
        }

        $validStatus = ['active', 'paused', 'archived'];
        $status = strtolower((string)($payload['status'] ?? 'active'));
        if (!in_array($status, $validStatus, true)) {
            $this->errorResponse('Invalid status. Allowed values: active, paused, archived', 400);
            return false;
        }

        if (strtolower((string)$payload['period']) === 'custom') {
            if (empty($payload['end_date']) || !$this->isValidDate($payload['end_date'])) {
                $this->errorResponse('custom period requires a valid end_date (YYYY-MM-DD)', 400);
                return false;
            }
        }

        if (!empty($payload['end_date']) && !$this->isValidDate($payload['end_date'])) {
            $this->errorResponse('Invalid end_date. Expected format: YYYY-MM-DD', 400);
            return false;
        }

        if (!empty($payload['end_date']) && strtotime($payload['end_date']) < strtotime($payload['start_date'])) {
            $this->errorResponse('end_date cannot be before start_date', 400);
            return false;
        }

        return true;
    }

    protected function resolveEndDate(string $period, string $startDate, ?string $endDate): ?string
    {
        $period = strtolower($period);
        if ($period === 'custom') {
            return $endDate;
        }

        try {
            $date = new \DateTime($startDate);
            switch ($period) {
                case 'weekly':
                    $date->modify('+6 day');
                    break;
                case 'monthly':
                    $date->modify('+1 month -1 day');
                    break;
                case 'yearly':
                    $date->modify('+1 year -1 day');
                    break;
                default:
                    return null;
            }

            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function isValidDate($date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    protected function calculateSpent(int $userId, array $budgetRow): float
    {
        $db = \Base::instance()->get('DB');
        $dateColumn = $this->getTransactionDateColumn();

        $sql = "
            SELECT COALESCE(SUM(t.monto), 0) AS spent
            FROM transacciones t
            WHERE t.usuario_id = ?
              AND LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso')
              AND DATE(t.$dateColumn) BETWEEN ? AND ?
        ";

        $params = [
            $userId,
            $budgetRow['start_date'],
            $budgetRow['end_date']
        ];

        if (!empty($budgetRow['category_id'])) {
            $sql .= ' AND t.categoria_id = ?';
            $params[] = $budgetRow['category_id'];
        }

        $result = $db->exec($sql, $params);
        if (!is_array($result) || !isset($result[0]['spent'])) {
            return 0.0;
        }

        return round((float)$result[0]['spent'], 2);
    }

    protected function getTransactionDateColumn(): string
    {
        if ($this->transactionDateColumn !== null) {
            return $this->transactionDateColumn;
        }

        $db = \Base::instance()->get('DB');

        $hasFechaRegistro = $db->exec("SHOW COLUMNS FROM transacciones LIKE 'fecha_registro'");
        if (!empty($hasFechaRegistro)) {
            $this->transactionDateColumn = 'fecha_registro';
            return $this->transactionDateColumn;
        }

        $hasFecha = $db->exec("SHOW COLUMNS FROM transacciones LIKE 'fecha'");
        if (!empty($hasFecha)) {
            $this->transactionDateColumn = 'fecha';
            return $this->transactionDateColumn;
        }

        $this->transactionDateColumn = 'fecha_registro';
        return $this->transactionDateColumn;
    }
}
