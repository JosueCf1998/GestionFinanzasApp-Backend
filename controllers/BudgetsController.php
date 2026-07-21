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

    public function register($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $payload = $this->normalizeBudgetInput($body, null);

        if (!$this->validateBudgetPayload($payload)) {
            return;
        }

        $userId = (int)$decoded->data->user_id;
        $validCategories = $this->filterAndValidateCategoryItems(
            $payload['categories'],
            $userId
        );

        if ($validCategories === null) {
            return;
        }

        $validAccountIds = $this->filterValidAccountIds(
            $payload['account_ids'],
            $userId
        );

        if (count($validAccountIds) !== count($payload['account_ids'])) {
            $this->errorResponse(
                'Una o más cuentas no existen o no pertenecen al usuario',
                400
            );
            return;
        }

        $totalAmount = $this->calculateBudgetTotal($validCategories);
$db = \Base::instance()->get('DB');

        try {
            $db->begin();

            $this->budgetModel->reset();
            $this->budgetModel->set('user_id', $userId);
            $this->budgetModel->set('budget_series_id', null);
            $this->budgetModel->set('name', $payload['name']);
            $this->budgetModel->set('icon', $payload['icon']);
            $this->budgetModel->set('color', $payload['color']);
            $this->budgetModel->set('amount', $totalAmount);
            $this->budgetModel->set('start_date', $payload['start_date']);
            $this->budgetModel->set('end_date', $payload['end_date']);
            $this->budgetModel->set('status', $payload['status']);
            $this->budgetModel->set('notes', $payload['notes']);

            if (!$this->budgetModel->save()) {
                throw new \RuntimeException(
                    'No se pudo guardar el presupuesto'
                );
            }

            $budgetId = (int)$this->budgetModel->get('id');

            // Cada presupuesto inicia una serie propia.
            $this->setBudgetSeriesId($budgetId, $budgetId);

            $this->syncBudgetCategoryItems(
                $budgetId,
                $validCategories
            );

            $this->syncBudgetAccounts(
                $budgetId,
                $validAccountIds
            );

            $db->commit();

            $this->successResponse([
                'message' => 'Presupuesto creado correctamente'
            ]);
        } catch (\Throwable $e) {
            $db->rollback();
            $this->errorResponse(
                'No se pudo crear el presupuesto: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * PUT/PATCH /budgets/update
     *
     * Permite editar nombre, fechas, repetición, cuentas y
     * categorías con sus montos. El total se vuelve a calcular.
     */
    public function update($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $budgetId = (int)($body['budget_id'] ?? $body['id'] ?? 0);
        if ($budgetId <= 0) {
            $this->errorResponse('El ID del presupuesto es obligatorio', 400);
            return;
        }

        $userId = (int)$decoded->data->user_id;

        $this->budgetModel->load([
            'id = ? AND user_id = ?',
            $budgetId,
            $userId
        ]);

        if (!$this->budgetModel->loaded()) {
            $this->errorResponse(
                'El presupuesto no existe o no pertenece al usuario',
                404
            );
            return;
        }

        $current = $this->budgetModel->cast();
        $current['account_ids'] = $this->getBudgetAccountIds($budgetId);
        $current['categories'] = $this->getBudgetCategoryItems($budgetId);

        $payload = $this->normalizeBudgetInput($body, $current);

        if (!$this->validateBudgetPayload($payload)) {
            return;
        }

        $validCategories = $this->filterAndValidateCategoryItems(
            $payload['categories'],
            $userId
        );

        if ($validCategories === null) {
            return;
        }

        $validAccountIds = $this->filterValidAccountIds(
            $payload['account_ids'],
            $userId
        );

        if (count($validAccountIds) !== count($payload['account_ids'])) {
            $this->errorResponse(
                'Una o más cuentas no existen o no pertenecen al usuario',
                400
            );
            return;
        }

        $totalAmount = $this->calculateBudgetTotal($validCategories);
$db = \Base::instance()->get('DB');

        try {
            $db->begin();
            $this->budgetModel->set('name', $payload['name']);
            $this->budgetModel->set('icon', $payload['icon']);
            $this->budgetModel->set('color', $payload['color']);
            $this->budgetModel->set('amount', $totalAmount);
            $this->budgetModel->set('start_date', $payload['start_date']);
            $this->budgetModel->set('end_date', $payload['end_date']);
            $this->budgetModel->set('status', $payload['status']);
            $this->budgetModel->set('notes', $payload['notes']);

            if (!$this->budgetModel->save()) {
                throw new \RuntimeException(
                    'No se pudo actualizar el presupuesto'
                );
            }

            $this->syncBudgetCategoryItems(
                $budgetId,
                $validCategories
            );

            $this->syncBudgetAccounts(
                $budgetId,
                $validAccountIds
            );

            $db->commit();

            $this->successResponse([
                'message' => 'Presupuesto actualizado correctamente'
            ]);
        } catch (\Throwable $e) {
            $db->rollback();
            $this->errorResponse(
                'No se pudo actualizar el presupuesto: ' . $e->getMessage(),
                500
            );
        }
    }

    public function delete($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $budgetId = (int)($body['budget_id'] ?? $body['id'] ?? 0);
        if ($budgetId <= 0) {
            $this->errorResponse('El ID del presupuesto es obligatorio', 400);
            return;
        }

        $this->budgetModel->load([
            'id = ? AND user_id = ?',
            $budgetId,
            (int)$decoded->data->user_id
        ]);

        if (!$this->budgetModel->loaded()) {
            $this->errorResponse(
                'El presupuesto no existe o no pertenece al usuario',
                404
            );
            return;
        }

        if (!$this->budgetModel->erase()) {
            $this->errorResponse('No se pudo eliminar el presupuesto', 500);
            return;
        }

        $this->successResponse([
            'message' => 'Presupuesto eliminado correctamente',
            'info' => ['id' => $budgetId]
        ]);
    }

    /**
     * GET /budgets/list
     * Devuelve todos los presupuestos del usuario
     */
    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $userId = (int)$decoded->data->user_id;
        $rows = $this->budgetModel->find(
            ['user_id = ?', $userId],
            ['order' => 'created_at DESC, id DESC']
        );

        $items = [];
        $totalBudgeted = 0.0;
        $totalSpent = 0.0;
        $activeCount = 0;
        $pausedCount = 0;
        $archivedCount = 0;

        foreach ($rows as $budget) {
            $row = $budget->cast();
            $item = $this->buildBudgetListItem($row, $userId);
            $items[] = $item;

            $recordStatus = strtolower((string)$row['status']);

            if ($recordStatus === 'active') {
                $activeCount++;
                $totalBudgeted += $item['budgeted'];
                $totalSpent += $item['spent'];
            } elseif ($recordStatus === 'paused') {
                $pausedCount++;
            } elseif ($recordStatus === 'archived') {
                $archivedCount++;
            }
        }

        $this->successResponse([
            'detail' => [
                'total_budgeted' => round($totalBudgeted, 2),
                'total_spent' => round($totalSpent, 2),
                'usage_percent' => $totalBudgeted > 0
                    ? round(($totalSpent / $totalBudgeted) * 100, 2)
                    : 0, 
                'total_budgets' => count($items)
            ],
            'items' => $items,
            'message' => count($items) > 0
                ? 'Listado general de presupuestos generado correctamente'
                : 'No hay presupuestos registrados'
        ]);
    }

    /**
     * POST /budgets/filter
     * JSON: {"startDate":"2026-07-01","endDate":"2026-07-31"}
     */
    public function filter($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $userId = (int)$decoded->data->user_id;
        $startDate = $body['startDate'] ?? $body['start_date'] ?? null;
        $endDate = $body['endDate'] ?? $body['end_date'] ?? null;

        if (!$this->isValidDate($startDate)) {
            $this->errorResponse('startDate must use YYYY-MM-DD format', 400);
            return;
        }

        if (!$this->isValidDate($endDate)) {
            $this->errorResponse('endDate must use YYYY-MM-DD format', 400);
            return;
        }

        if (strtotime($endDate) < strtotime($startDate)) {
            $this->errorResponse('endDate cannot be earlier than startDate', 400);
            return;
        }

        $where = [
            'b.user_id = ?',
            'b.status = ?',
            'b.start_date <= ?',
            'b.end_date >= ?'
        ];
        $params = [$userId, 'active', $endDate, $startDate];

        $db = \Base::instance()->get('DB');
        $sql = "SELECT b.* FROM budgets b WHERE "
             . implode(' AND ', $where)
             . " ORDER BY b.created_at DESC, b.id DESC";

        $rows = $db->exec($sql, $params);

        $totalBudget = 0.0;
        $totalSpent = 0.0;
        $budgetList = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $budgetId = (int)$row['id'];
                $categories = $this->getBudgetCategoryItems($budgetId);
                $accountIds = $this->getBudgetAccountIds($budgetId);
                $categoryIds = array_map(
                    fn($item) => (int)$item['category_id'],
                    $categories
                );

                $effectiveStart = max($row['start_date'], $startDate);
                $effectiveEnd = min($row['end_date'], $endDate);

                $spent = $this->calculateSpent(
                    $userId,
                    $effectiveStart,
                    $effectiveEnd,
                    $categoryIds,
                    $accountIds
                );

                $budgeted = round((float)$row['amount'], 2);
                $percentage = $budgeted > 0
                    ? round(($spent / $budgeted) * 100, 2)
                    : 0;

                if ($spent > $budgeted) {
                    $status = 'EXCEEDED';
                } elseif ($percentage >= 80) {
                    $status = 'WARNING';
                } else {
                    $status = 'ON_TRACK';
                }

                $totalBudget += $budgeted;
                $totalSpent += $spent;

                $budgetList[] = [
                    'id' => $budgetId,
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'name' => $row['name'],
                    'icon' => $row['icon'] ?? null,
                    'color' => $row['color'] ?? null,
                    'status' => $status,
                    'percentage' => $percentage,
                    'spentAmount' => round($spent, 2),
                    'budgetAmount' => $budgeted
                ];
            }
        }

        $totalBudget = round($totalBudget, 2);
        $totalSpent = round($totalSpent, 2);
        $usagePercentage = $totalBudget > 0
            ? round(($totalSpent / $totalBudget) * 100, 2)
            : 0;

        $this->sendBudgetFilterResponse([
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'usagePercentage' => $usagePercentage,
            'budgetList' => $budgetList
        ]);
    }

    protected function sendBudgetFilterResponse(array $data): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'success' => true,
                'message' => 'Budgets retrieved successfully',
                'data' => $data,
                'statusCode' => 200,
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    protected function buildBudgetListItem(array $row, int $userId): array
    {
        $budgetId = (int)$row['id'];
        $categories = $this->getBudgetCategoryItems($budgetId);
        $accountIds = $this->getBudgetAccountIds($budgetId);
        $accounts = $this->getBudgetAccounts($budgetId);
        $categoryIds = array_map(fn($item) => (int)$item['category_id'], $categories);

        $spent = $this->calculateSpent(
            $userId,
            $row['start_date'],
            $row['end_date'],
            $categoryIds,
            $accountIds
        );

        $budgeted = (float)$row['amount'];
        $remaining = round($budgeted - $spent, 2);
        $percentage = $budgeted > 0 ? round(($spent / $budgeted) * 100, 2) : 0;

        if ($spent > $budgeted) {
            $budgetStatus = 'EXCEEDED';
        } elseif ($percentage >= 80) {
            $budgetStatus = 'WARNING';
        } else {
            $budgetStatus = 'ON_TRACK';
        }

        return [
            'id' => $budgetId,
            'name' => $row['name'],
            'icon' => $row['icon'] ?? null,
            'color' => $row['color'] ?? null,
            'status' => $budgetStatus,
            'percentage' => $percentage,
            'spent' => $spent,
            'budgeted' => $budgeted
        ];
    }

    public function detail($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $body = $this->parseJsonOrEncryptedBody($f3);

        if (empty($body)) {
            return;
        }

        $budgetId = (int)(
            $body['budget_id']
            ?? $body['id']
            ?? 0
        );

        if ($budgetId <= 0) {
            $this->errorResponse('Budget id is required', 400);
            return;
        }
        $userId = (int)$decoded->data->user_id;

        $this->budgetModel->load([
            'id = ? AND user_id = ?',
            $budgetId,
            $userId
        ]);

        if (!$this->budgetModel->loaded()) {
            $this->errorResponse(
                'Budget not found or not accessible',
                404
            );
            return;
        }

        $row = $this->budgetModel->cast();
        $categories = $this->getBudgetCategoryItems($budgetId);
        $accountIds = $this->getBudgetAccountIds($budgetId);
        $accounts = $this->getBudgetAccounts($budgetId);

        $categoryDetails = [];
        $totalSpent = 0.0;

        foreach ($categories as $category) {
            $allocatedAmount = round((float)$category['amount'], 2);

            $spentAmount = $this->calculateSpent(
                $userId,
                $row['start_date'],
                $row['end_date'],
                [(int)$category['category_id']],
                $accountIds
            );

            $percentage = $allocatedAmount > 0
                ? round(($spentAmount / $allocatedAmount) * 100, 2)
                : 0;

            $categoryStatus = $this->resolveBudgetStatus(
                $spentAmount,
                $allocatedAmount,
                $percentage
            );

            $totalSpent += $spentAmount;

            $categoryDetails[] = [
                'id' => (int)$category['category_id'],
                'name' => $category['name'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'status' => $categoryStatus,
                'percentage' => $percentage,
                'spentAmount' => round($spentAmount, 2),
                'budgetAmount' => $allocatedAmount,
                'remainingAmount' => round(
                    $allocatedAmount - $spentAmount,
                    2
                )
            ];
        }

        $totalBudget = round((float)$row['amount'], 2);
        $totalSpent = round($totalSpent, 2);
        $usagePercentage = $totalBudget > 0
            ? round(($totalSpent / $totalBudget) * 100, 2)
            : 0;

        $budgetStatus = $this->resolveBudgetStatus(
            $totalSpent,
            $totalBudget,
            $usagePercentage
        );

        $this->sendBudgetDetailResponse([
            'id' => $budgetId,
            'name' => $row['name'],
            'icon' => $row['icon'] ?? null,
            'color' => $row['color'] ?? null,
            'alert' => $this->buildBudgetAlert(
                $budgetStatus,
                $usagePercentage
            ),
            'generalDetail' => [
                'status' => $budgetStatus,
                'recordStatus' => $row['status'],
                'startDate' => $row['start_date'],
                'endDate' => $row['end_date'],
                'totalBudget' => $totalBudget,
                'totalSpent' => $totalSpent,
                'remainingAmount' => round(
                    $totalBudget - $totalSpent,
                    2
                ),
                'usagePercentage' => $usagePercentage,
                'notes' => $row['notes']
            ],
            'linkedAccounts' => array_map(
                function ($account) {
                    return [
                        'id' => (int)$account['id'],
                        'name' => $account['name'],
                        'amount' => (float)$account['amount'],
                        'icon' => $account['icon'],
                        'color' => $account['color']
                    ];
                },
                $accounts
            ),
            'linkedCategories' => $categoryDetails,
            'suggestion' => $this->buildBudgetSuggestion(
                $budgetStatus,
                $usagePercentage,
                $totalBudget - $totalSpent
            ),
            'availableActions' => [
                'canEdit' => true,
                'canArchive' => $row['status'] !== 'archived',
                'canDelete' => true
            ]
        ]);
    }

    protected function resolveBudgetStatus(
        float $spent,
        float $budgeted,
        float $percentage
    ): string {
        if ($spent > $budgeted) {
            return 'EXCEEDED';
        }

        if ($percentage >= 80) {
            return 'WARNING';
        }

        return 'ON_TRACK';
    }

    protected function buildBudgetAlert(
        string $status,
        float $percentage
    ): array {
        switch ($status) {
            case 'EXCEEDED':
                return [
                    'type' => 'ERROR',
                    'title' => 'Budget exceeded',
                    'message' => 'You have exceeded the assigned budget.'
                ];

            case 'WARNING':
                return [
                    'type' => 'WARNING',
                    'title' => 'Budget near the limit',
                    'message' => 'You have used ' . $percentage . '% of this budget.'
                ];

            default:
                return [
                    'type' => 'INFO',
                    'title' => 'Budget on track',
                    'message' => 'Your spending remains within the planned budget.'
                ];
        }
    }

    protected function buildBudgetSuggestion(
        string $status,
        float $percentage,
        float $remainingAmount
    ): string {
        if ($status === 'EXCEEDED') {
            return 'Review the categories with the highest spending and reduce non-essential expenses.';
        }

        if ($status === 'WARNING') {
            return 'You are close to the budget limit. Prioritize essential expenses for the rest of the period.';
        }

        return 'You are managing this budget well. You still have '
            . round($remainingAmount, 2)
            . ' available.';
    }

    protected function sendBudgetDetailResponse(array $data): void
    {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            [
                'success' => true,
                'message' => 'Budget detail retrieved successfully',
                'data' => $data,
                'statusCode' => 200,
                'timestamp' => gmdate('Y-m-d\\TH:i:s\\Z')
            ],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    protected function normalizeBudgetInput(
        array $body,
        ?array $current
    ): array {
        $categories = $body['categories']
            ?? $body['category_items']
            ?? ($current['categories'] ?? []);

        $accountIds = $this->normalizeIdArray(
            $body['account_ids']
                ?? $body['account_id']
                ?? ($current['account_ids'] ?? [])
        );

        return [
            'name' => trim((string)(
                $body['name'] ?? ($current['name'] ?? '')
            )),
            'icon' => trim((string)(
                $body['icon'] ?? ($current['icon'] ?? '')
            )),
            'color' => trim((string)(
                $body['color'] ?? ($current['color'] ?? '')
            )),
            'start_date' => $body['start_date']
                ?? $body['startDate']
                ?? ($current['start_date'] ?? null),
            'end_date' => $body['end_date']
                ?? $body['endDate']
                ?? ($current['end_date'] ?? null),
            'account_ids' => $accountIds,
            'categories' => $this->normalizeCategoryItems($categories),
            'status' => strtolower(trim((string)(
                $body['status'] ?? ($current['status'] ?? 'active')
            ))),
            'notes' => array_key_exists('notes', $body)
                ? $body['notes']
                : ($current['notes'] ?? null)
        ];
    }

    protected function validateBudgetPayload(array $payload): bool
    {
        if ($payload['name'] === '') {
            $this->errorResponse(
                'El nombre del presupuesto es obligatorio',
                400
            );
            return false;
        }

        if (!$this->isValidDate($payload['start_date'])) {
            $this->errorResponse(
                'start_date debe tener formato YYYY-MM-DD',
                400
            );
            return false;
        }

        if (!$this->isValidDate($payload['end_date'])) {
            $this->errorResponse(
                'end_date debe tener formato YYYY-MM-DD',
                400
            );
            return false;
        }

        if (strtotime($payload['end_date']) < strtotime($payload['start_date'])) {
            $this->errorResponse(
                'end_date no puede ser menor que start_date',
                400
            );
            return false;
        }

        if (empty($payload['account_ids'])) {
            $this->errorResponse(
                'Debe seleccionar al menos una cuenta',
                400
            );
            return false;
        }

        if (empty($payload['categories'])) {
            $this->errorResponse(
                'Debe seleccionar al menos una categoría',
                400
            );
            return false;
        }

        foreach ($payload['categories'] as $item) {
            if (
                empty($item['category_id'])
                || !is_numeric($item['amount'])
                || (float)$item['amount'] <= 0
            ) {
                $this->errorResponse(
                    'Cada categoría debe tener category_id y un amount mayor que cero',
                    400
                );
                return false;
            }
        }

        if (!in_array(
            $payload['status'],
            ['active', 'paused', 'archived'],
            true
        )) {
            $this->errorResponse(
                'Estado inválido. Valores: active, paused, archived',
                400
            );
            return false;
        }

        return true;
    }

    protected function normalizeCategoryItems($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $grouped = [];

        foreach ($value as $item) {
            if (!is_array($item)) {
                continue;
            }

            $categoryId = (int)(
                $item['category_id']
                ?? $item['id']
                ?? 0
            );

            $amount = $item['amount']
                ?? $item['monto']
                ?? null;

            if ($categoryId <= 0 || !is_numeric($amount)) {
                continue;
            }

            // Si la misma categoría llega repetida, suma sus montos.
            if (!isset($grouped[$categoryId])) {
                $grouped[$categoryId] = 0.0;
            }

            $grouped[$categoryId] += (float)$amount;
        }

        $items = [];

        foreach ($grouped as $categoryId => $amount) {
            $items[] = [
                'category_id' => (int)$categoryId,
                'amount' => round($amount, 2)
            ];
        }

        return $items;
    }

    protected function filterAndValidateCategoryItems(
        array $items,
        int $userId
    ): ?array {
        $ids = array_values(array_unique(array_map(
            fn($item) => (int)$item['category_id'],
            $items
        )));

        if (empty($ids)) {
            $this->errorResponse(
                'Debe seleccionar categorías válidas',
                400
            );
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $userId;

        $db = \Base::instance()->get('DB');

        $rows = $db->exec(
            "SELECT id, nombre, icono, color
             FROM categorias
             WHERE id IN ($placeholders)
               AND (usuario_id = ? OR usuario_id IS NULL)
               AND LOWER(COALESCE(tipo, '')) IN ('gasto', 'expense', 'egreso')",
            $params
        );

        if (!is_array($rows) || count($rows) !== count($ids)) {
            $this->errorResponse(
                'Una o más categorías no existen, no son de gasto o no están disponibles para el usuario',
                400
            );
            return null;
        }

        $metadata = [];
        foreach ($rows as $row) {
            $metadata[(int)$row['id']] = $row;
        }

        $validated = [];
        foreach ($items as $item) {
            $id = (int)$item['category_id'];

            if (!isset($metadata[$id])) {
                continue;
            }

            $validated[] = [
                'category_id' => $id,
                'name' => $metadata[$id]['nombre'],
                'icon' => $metadata[$id]['icono'],
                'color' => $metadata[$id]['color'],
                'amount' => round((float)$item['amount'], 2)
            ];
        }

        return $validated;
    }

    protected function calculateBudgetTotal(array $categories): float
    {
        $total = 0.0;

        foreach ($categories as $item) {
            $total += (float)$item['amount'];
        }

        return round($total, 2);
    }

    protected function syncBudgetCategoryItems(
        int $budgetId,
        array $categories
    ): void {
        $db = \Base::instance()->get('DB');

        $db->exec(
            'DELETE FROM budget_categories WHERE budget_id = ?',
            [$budgetId]
        );

        foreach ($categories as $item) {
            $db->exec(
                'INSERT INTO budget_categories
                    (budget_id, category_id, allocated_amount)
                 VALUES (?, ?, ?)',
                [
                    $budgetId,
                    (int)$item['category_id'],
                    (float)$item['amount']
                ]
            );
        }
    }

    protected function syncBudgetAccounts(
        int $budgetId,
        array $accountIds
    ): void {
        $db = \Base::instance()->get('DB');

        $db->exec(
            'DELETE FROM budget_accounts WHERE budget_id = ?',
            [$budgetId]
        );

        foreach ($accountIds as $accountId) {
            $db->exec(
                'INSERT INTO budget_accounts (budget_id, account_id)
                 VALUES (?, ?)',
                [$budgetId, (int)$accountId]
            );
        }
    }

    protected function getBudgetCategoryItems(int $budgetId): array
    {
        $db = \Base::instance()->get('DB');

        $rows = $db->exec(
            "SELECT
                bc.category_id,
                bc.allocated_amount AS amount,
                c.nombre AS name,
                c.icono AS icon,
                c.color
             FROM budget_categories bc
             INNER JOIN categorias c ON c.id = bc.category_id
             WHERE bc.budget_id = ?
             ORDER BY c.nombre ASC",
            [$budgetId]
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(function ($row) {
            return [
                'category_id' => (int)$row['category_id'],
                'amount' => (float)$row['amount'],
                'name' => $row['name'],
                'icon' => $row['icon'],
                'color' => $row['color']
            ];
        }, $rows);
    }

    protected function getBudgetAccountIds(int $budgetId): array
    {
        $db = \Base::instance()->get('DB');

        $rows = $db->exec(
            'SELECT account_id
             FROM budget_accounts
             WHERE budget_id = ?',
            [$budgetId]
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_map(
            fn($row) => (int)$row['account_id'],
            $rows
        );
    }

    protected function getBudgetAccounts(int $budgetId): array
    {
        $db = \Base::instance()->get('DB');

        $rows = $db->exec(
            "SELECT a.id, a.nombre AS name, a.saldo AS amount,
                    a.icon, a.color
             FROM budget_accounts ba
             INNER JOIN cuentas a ON a.id = ba.account_id
             WHERE ba.budget_id = ?
             ORDER BY a.nombre ASC",
            [$budgetId]
        );

        return is_array($rows) ? $rows : [];
    }

    protected function filterValidAccountIds(
        array $accountIds,
        int $userId
    ): array {
        $accountIds = array_values(array_unique(array_map(
            'intval',
            $accountIds
        )));

        if (empty($accountIds)) {
            return [];
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($accountIds), '?')
        );

        $params = $accountIds;
        $params[] = $userId;

        $db = \Base::instance()->get('DB');

        $rows = $db->exec(
            "SELECT id
             FROM cuentas
             WHERE id IN ($placeholders)
               AND usuario_id = ?",
            $params
        );

        if (!is_array($rows)) {
            return [];
        }

        return array_values(array_unique(array_map(
            fn($row) => (int)$row['id'],
            $rows
        )));
    }

    protected function calculateSpent(
        int $userId,
        string $startDate,
        string $endDate,
        array $categoryIds,
        array $accountIds
    ): float {
        if (
            empty($categoryIds)
            || empty($accountIds)
            || strtotime($endDate) < strtotime($startDate)
        ) {
            return 0.0;
        }

        $db = \Base::instance()->get('DB');
        $dateColumn = $this->getTransactionDateColumn();

        $catPlaceholders = implode(
            ',',
            array_fill(0, count($categoryIds), '?')
        );
        $accPlaceholders = implode(
            ',',
            array_fill(0, count($accountIds), '?')
        );

        $sql = "
            SELECT COALESCE(SUM(t.monto), 0) AS spent
            FROM transacciones t
            WHERE t.usuario_id = ?
              AND LOWER(COALESCE(t.tipo, ''))
                    IN ('gasto', 'expense', 'egreso')
              AND DATE(t.$dateColumn) BETWEEN ? AND ?
              AND t.categoria_id IN ($catPlaceholders)
              AND t.cuenta_id IN ($accPlaceholders)
        ";

        $params = [$userId, $startDate, $endDate];

        foreach ($categoryIds as $categoryId) {
            $params[] = (int)$categoryId;
        }

        foreach ($accountIds as $accountId) {
            $params[] = (int)$accountId;
        }

        $result = $db->exec($sql, $params);

        if (!is_array($result) || !isset($result[0]['spent'])) {
            return 0.0;
        }

        return round((float)$result[0]['spent'], 2);
    }

    protected function setBudgetSeriesId(
        int $budgetId,
        int $seriesId
    ): void {
        $db = \Base::instance()->get('DB');

        $db->exec(
            'UPDATE budgets
             SET budget_series_id = ?
             WHERE id = ?',
            [$seriesId, $budgetId]
        );
    }

    protected function normalizeIdArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (!is_array($value)) {
            $value = strpos((string)$value, ',') !== false
                ? explode(',', (string)$value)
                : [$value];
        }

        $ids = [];

        foreach ($value as $item) {
            if (is_numeric($item) && (int)$item > 0) {
                $ids[] = (int)$item;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function isValidDate($date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);

        return $parsed
            && $parsed->format('Y-m-d') === $date;
    }

    protected function getTransactionDateColumn(): string
    {
        if ($this->transactionDateColumn !== null) {
            return $this->transactionDateColumn;
        }

        $db = \Base::instance()->get('DB');

        $fecha = $db->exec(
            "SHOW COLUMNS FROM transacciones LIKE 'fecha'"
        );

        if (!empty($fecha)) {
            $this->transactionDateColumn = 'fecha';
            return $this->transactionDateColumn;
        }

        $fechaRegistro = $db->exec(
            "SHOW COLUMNS FROM transacciones LIKE 'fecha_registro'"
        );

        if (!empty($fechaRegistro)) {
            $this->transactionDateColumn = 'fecha_registro';
            return $this->transactionDateColumn;
        }

        $this->transactionDateColumn = 'fecha_registro';
        return $this->transactionDateColumn;
    }
}
