<?php

class DashboardModel
{
    /** @var \DB\SQL */
    private $db;

    /** @var string|null */
    private $transactionDateColumn = null;

    public function __construct()
    {
        $this->db = \Base::instance()->get('DB');
    }

    public function validateUserAccounts(int $userId, array $accountIds): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $accountIds = array_values(array_unique(array_map('intval', $accountIds)));
        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $params = $accountIds;
        $params[] = $userId;

        $rows = $this->db->exec(
            "SELECT id FROM cuentas WHERE id IN ($placeholders) AND usuario_id = ?",
            $params
        );

        if (!is_array($rows)) {
            return [];
        }

        $validMap = [];
        foreach ($rows as $row) {
            $validMap[(int)$row['id']] = true;
        }

        $validIds = [];
        foreach ($accountIds as $accountId) {
            if (isset($validMap[$accountId])) {
                $validIds[] = $accountId;
            }
        }

        return $validIds;
    }

    public function getSummary(int $userId, string $fechaInicio, string $fechaFin, array $accountIds): array
    {
        $filters = $this->buildFiltersPayload($fechaInicio, $fechaFin, $accountIds);
        $periodTotals = $this->getIncomeExpenseTotalsByRange($userId, $fechaInicio, $fechaFin, $accountIds);
        $budgetProgress = $this->calculateBudgetProgressData($userId, $fechaInicio, $fechaFin, $accountIds);
        $currentBalance = $this->getCurrentBalance($userId, $accountIds);

        $totalIncome = $periodTotals['income'];
        $totalExpenses = $periodTotals['expenses'];
        $periodBalance = round($totalIncome - $totalExpenses, 2);
        $totalBudget = $budgetProgress['totals']['budgeted'];
        $budgetSpent = $budgetProgress['totals']['spent'];
        $budgetRemaining = round($totalBudget - $budgetSpent, 2);
        $savingsRate = $totalIncome > 0
            ? round(($periodBalance / $totalIncome) * 100, 2)
            : 0.0;

        return [
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'period_balance' => $periodBalance,
                'current_balance' => $currentBalance,
                'total_budget' => $totalBudget,
                'budget_spent' => $budgetSpent,
                'budget_remaining' => $budgetRemaining,
                'savings_rate' => $savingsRate
            ]
        ];
    }

    public function getExpensesByCategory(int $userId, string $fechaInicio, string $fechaFin, array $accountIds): array
    {
        $stats = $this->getExpenseCategoryStats($userId, $fechaInicio, $fechaFin, $accountIds, null);

        return [
            'total_expenses' => $stats['total_expenses'],
            'items' => $stats['items']
        ];
    }

    public function getIncomeVsExpenses(int $userId, string $fechaInicio, string $fechaFin, array $accountIds): array
    {
        $periods = $this->generateMonthlyPeriods($fechaInicio, $fechaFin);
        $monthlyMap = $this->getIncomeVsExpenseMapByRange($userId, $fechaInicio, $fechaFin, $accountIds);

        return [
            'items' => $this->buildIncomeExpenseItems(
                $periods,
                $monthlyMap,
                $this->spansMultipleYears($fechaInicio, $fechaFin)
            )
        ];
    }

    public function getBalanceEvolution(int $userId, string $fechaInicio, string $fechaFin, array $accountIds): array
    {
        $periods = $this->generateMonthlyPeriods($fechaInicio, $fechaFin);
        $monthlyMap = $this->getIncomeVsExpenseMapByRange($userId, $fechaInicio, $fechaFin, $accountIds);
        $currentBalance = $this->getCurrentBalance($userId, $accountIds);
        $netMovementFromStart = $this->getNetMovementFromDate($userId, $fechaInicio, $accountIds);
        $initialBalance = round($currentBalance - $netMovementFromStart, 2);

        $items = [];
        $accumulated = $initialBalance;
        $multipleYears = $this->spansMultipleYears($fechaInicio, $fechaFin);

        foreach ($periods as $period) {
            $periodKey = $period['period'];
            $income = isset($monthlyMap[$periodKey]) ? $monthlyMap[$periodKey]['income'] : 0.0;
            $expenses = isset($monthlyMap[$periodKey]) ? $monthlyMap[$periodKey]['expenses'] : 0.0;
            $monthlyBalance = round($income - $expenses, 2);
            $accumulated = round($accumulated + $monthlyBalance, 2);

            $items[] = [
                'period' => $periodKey,
                'month' => $this->formatMonthLabel($period['date'], $multipleYears),
                'income' => $income,
                'expenses' => $expenses,
                'monthly_balance' => $monthlyBalance,
                'accumulated_balance' => $accumulated
            ];
        }

        return [
            'initial_balance' => $initialBalance,
            'items' => $items
        ];
    }

    public function getBudgetProgress(int $userId, string $fechaInicio, string $fechaFin, array $accountIds): array
    {
        $result = $this->calculateBudgetProgressData($userId, $fechaInicio, $fechaFin, $accountIds);

        return [
            'totals' => $result['totals'],
            'items' => $result['items']
        ];
    }

    public function getTopExpenseCategories(int $userId, string $fechaInicio, string $fechaFin, array $accountIds, int $limit): array
    {
        $stats = $this->getExpenseCategoryStats($userId, $fechaInicio, $fechaFin, $accountIds, $limit);

        return [
            'limit' => $limit,
            'total_expenses' => $stats['total_expenses'],
            'items' => $stats['items']
        ];
    }

    private function getIncomeExpenseTotalsByRange(int $userId, string $startDate, string $endDate, array $accountIds): array
    {
        $dateColumn = $this->getTransactionDateColumn();
        $where = [
            't.usuario_id = ?',
            'DATE(t.' . $dateColumn . ') BETWEEN ? AND ?'
        ];
        $params = [$userId, $startDate, $endDate];
        $this->appendAccountFilter($where, $params, 't.cuenta_id', $accountIds);

        $sql = "
            SELECT
                COALESCE(SUM(CASE
                    WHEN LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('ingreso', 'income')
                    THEN t.monto ELSE 0
                END), 0) AS total_income,
                COALESCE(SUM(CASE
                    WHEN LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('gasto', 'expense', 'egreso')
                    THEN t.monto ELSE 0
                END), 0) AS total_expenses
            FROM transacciones t
            WHERE " . implode(' AND ', $where) . "
        ";

        $rows = $this->db->exec($sql, $params);
        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : [];

        return [
            'income' => round((float)($row['total_income'] ?? 0), 2),
            'expenses' => round((float)($row['total_expenses'] ?? 0), 2)
        ];
    }

    private function getCurrentBalance(int $userId, array $accountIds): float
    {
        $where = ['usuario_id = ?'];
        $params = [$userId];
        $this->appendAccountFilter($where, $params, 'id', $accountIds);

        $rows = $this->db->exec(
            'SELECT COALESCE(SUM(saldo), 0) AS total_balance FROM cuentas WHERE ' . implode(' AND ', $where),
            $params
        );

        if (!is_array($rows) || !isset($rows[0]['total_balance'])) {
            return 0.0;
        }

        return round((float)$rows[0]['total_balance'], 2);
    }

    private function getIncomeVsExpenseMapByRange(int $userId, string $startDate, string $endDate, array $accountIds): array
    {
        $dateColumn = $this->getTransactionDateColumn();
        $where = [
            't.usuario_id = ?',
            'DATE(t.' . $dateColumn . ') BETWEEN ? AND ?'
        ];
        $params = [$userId, $startDate, $endDate];
        $this->appendAccountFilter($where, $params, 't.cuenta_id', $accountIds);

        $sql = "
            SELECT
                DATE_FORMAT(DATE(t.$dateColumn), '%Y-%m') AS period_key,
                DATE_FORMAT(DATE(t.$dateColumn), '%Y-%m-01') AS period_start,
                COALESCE(SUM(CASE
                    WHEN LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('ingreso', 'income')
                    THEN t.monto ELSE 0
                END), 0) AS income,
                COALESCE(SUM(CASE
                    WHEN LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('gasto', 'expense', 'egreso')
                    THEN t.monto ELSE 0
                END), 0) AS expenses
            FROM transacciones t
            WHERE " . implode(' AND ', $where) . "
            GROUP BY DATE_FORMAT(DATE(t.$dateColumn), '%Y-%m'), DATE_FORMAT(DATE(t.$dateColumn), '%Y-%m-01')
            ORDER BY period_start ASC
        ";

        $rows = $this->db->exec($sql, $params);
        $result = [];

        if (!is_array($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            $periodKey = (string)$row['period_key'];
            $result[$periodKey] = [
                'income' => round((float)$row['income'], 2),
                'expenses' => round((float)$row['expenses'], 2)
            ];
        }

        return $result;
    }

    private function getNetMovementFromDate(int $userId, string $startDate, array $accountIds): float
    {
        $dateColumn = $this->getTransactionDateColumn();
        $where = [
            't.usuario_id = ?',
            'DATE(t.' . $dateColumn . ') >= ?'
        ];
        $params = [$userId, $startDate];
        $this->appendAccountFilter($where, $params, 't.cuenta_id', $accountIds);

        $sql = "
            SELECT COALESCE(SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('ingreso', 'income') THEN t.monto
                    WHEN LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('gasto', 'expense', 'egreso') THEN -t.monto
                    ELSE 0
                END
            ), 0) AS net_amount
            FROM transacciones t
            WHERE " . implode(' AND ', $where) . "
        ";

        $rows = $this->db->exec($sql, $params);
        if (!is_array($rows) || !isset($rows[0]['net_amount'])) {
            return 0.0;
        }

        return round((float)$rows[0]['net_amount'], 2);
    }

    private function calculateBudgetProgressData(int $userId, string $startDate, string $endDate, array $accountIds): array
    {
        $budgetRows = $this->getActiveBudgetRowsBySeries($userId, $startDate, $endDate, $accountIds);

        if (empty($budgetRows)) {
            return [
                'totals' => [
                    'budgeted' => 0.0,
                    'spent' => 0.0,
                    'remaining' => 0.0,
                    'usage_percentage' => 0.0
                ],
                'items' => []
            ];
        }

        $budgetIds = array_map(function ($row) {
            return (int)$row['id'];
        }, $budgetRows);

        $spentByBudget = $this->getSpentByBudgetIds($budgetIds, $startDate, $endDate, $accountIds);
        $categoryMeta = $this->getBudgetCategoryMetaByBudgetIds($budgetIds);

        $items = [];
        $totalBudgeted = 0.0;
        $totalSpent = 0.0;

        foreach ($budgetRows as $row) {
            $budgetId = (int)$row['id'];
            $budgeted = round((float)$row['amount'], 2);
            $spent = isset($spentByBudget[$budgetId]) ? round((float)$spentByBudget[$budgetId], 2) : 0.0;
            $remaining = round($budgeted - $spent, 2);
            $progress = $budgeted > 0
                ? round(($spent / $budgeted) * 100, 2)
                : 0.0;

            $meta = isset($categoryMeta[$budgetId]) ? $categoryMeta[$budgetId] : [
                'category_id' => null,
                'category_name' => 'Sin categoría'
            ];

            $items[] = [
                'budget_id' => $budgetId,
                'budget_name' => (string)$row['name'],
                'category_id' => $meta['category_id'],
                'category_name' => $meta['category_name'],
                'budgeted' => $budgeted,
                'spent' => $spent,
                'remaining' => $remaining,
                'progress_percentage' => $progress,
                'status' => $this->resolveBudgetStatus($progress),
                'is_overspent' => $progress > 100.0
            ];

            $totalBudgeted += $budgeted;
            $totalSpent += $spent;
        }

        $totalBudgeted = round($totalBudgeted, 2);
        $totalSpent = round($totalSpent, 2);

        return [
            'totals' => [
                'budgeted' => $totalBudgeted,
                'spent' => $totalSpent,
                'remaining' => round($totalBudgeted - $totalSpent, 2),
                'usage_percentage' => $totalBudgeted > 0
                    ? round(($totalSpent / $totalBudgeted) * 100, 2)
                    : 0.0
            ],
            'items' => $items
        ];
    }

    private function getActiveBudgetRowsBySeries(int $userId, string $startDate, string $endDate, array $accountIds): array
    {
        $sql = "
            SELECT b.id, b.name, b.amount, b.start_date, b.end_date
            FROM budgets b
            INNER JOIN (
                SELECT
                    COALESCE(budget_series_id, id) AS series_key,
                    MAX(id) AS budget_id
                FROM budgets
                WHERE user_id = ?
                  AND status = 'active'
                  AND start_date <= ?
                  AND end_date >= ?
                GROUP BY COALESCE(budget_series_id, id)
            ) latest ON latest.budget_id = b.id
            WHERE b.user_id = ?
        ";

        $params = [$userId, $endDate, $startDate, $userId];

        if (!empty($accountIds)) {
            $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
            $sql .= "
              AND EXISTS (
                    SELECT 1
                    FROM budget_accounts ba
                    WHERE ba.budget_id = b.id
                      AND ba.account_id IN ($placeholders)
                )
            ";

            foreach ($accountIds as $accountId) {
                $params[] = (int)$accountId;
            }
        }

        $sql .= ' ORDER BY b.start_date ASC, b.id ASC';

        $rows = $this->db->exec($sql, $params);
        return is_array($rows) ? $rows : [];
    }

    private function getSpentByBudgetIds(array $budgetIds, string $startDate, string $endDate, array $accountIds): array
    {
        if (empty($budgetIds)) {
            return [];
        }

        $dateColumn = $this->getTransactionDateColumn();
        $budgetPlaceholders = implode(',', array_fill(0, count($budgetIds), '?'));
        $accountFilter = '';

        if (!empty($accountIds)) {
            $accountPlaceholders = implode(',', array_fill(0, count($accountIds), '?'));
            $accountFilter = " AND ba.account_id IN ($accountPlaceholders)";
        }

        $sql = "
            SELECT
                b.id AS budget_id,
                COALESCE(SUM(t.monto), 0) AS spent
            FROM budgets b
            INNER JOIN budget_categories bc ON bc.budget_id = b.id
            INNER JOIN budget_accounts ba ON ba.budget_id = b.id
            LEFT JOIN transacciones t
                ON t.usuario_id = b.user_id
               AND t.categoria_id = bc.category_id
               AND t.cuenta_id = ba.account_id
               AND LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('gasto', 'expense', 'egreso')
               AND DATE(t.$dateColumn) BETWEEN GREATEST(b.start_date, ?) AND LEAST(b.end_date, ?)
            WHERE b.id IN ($budgetPlaceholders)$accountFilter
            GROUP BY b.id
        ";

        $params = [$startDate, $endDate];
        foreach ($budgetIds as $budgetId) {
            $params[] = (int)$budgetId;
        }
        foreach ($accountIds as $accountId) {
            $params[] = (int)$accountId;
        }

        $rows = $this->db->exec($sql, $params);
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['budget_id']] = round((float)$row['spent'], 2);
        }

        return $result;
    }

    private function getBudgetCategoryMetaByBudgetIds(array $budgetIds): array
    {
        if (empty($budgetIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($budgetIds), '?'));

        $sql = "
            SELECT
                bc.budget_id,
                COUNT(*) AS total_categories,
                MIN(bc.category_id) AS single_category_id,
                MIN(c.nombre) AS single_category_name
            FROM budget_categories bc
            LEFT JOIN categorias c ON c.id = bc.category_id
            WHERE bc.budget_id IN ($placeholders)
            GROUP BY bc.budget_id
        ";

        $rows = $this->db->exec($sql, $budgetIds);
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $budgetId = (int)$row['budget_id'];
            if ((int)$row['total_categories'] === 1) {
                $result[$budgetId] = [
                    'category_id' => (int)$row['single_category_id'],
                    'category_name' => (string)$row['single_category_name']
                ];
                continue;
            }

            $result[$budgetId] = [
                'category_id' => null,
                'category_name' => 'Múltiples categorías'
            ];
        }

        return $result;
    }

    private function getExpenseCategoryStats(int $userId, string $startDate, string $endDate, array $accountIds, ?int $limit): array
    {
        $dateColumn = $this->getTransactionDateColumn();
        $where = [
            't.usuario_id = ?',
            "LOWER(TRIM(COALESCE(t.tipo, ''))) IN ('gasto', 'expense', 'egreso')",
            'DATE(t.' . $dateColumn . ') BETWEEN ? AND ?'
        ];
        $params = [$userId, $startDate, $endDate];
        $this->appendAccountFilter($where, $params, 't.cuenta_id', $accountIds);

        $totalSql = "
            SELECT COALESCE(SUM(t.monto), 0) AS total_expenses
            FROM transacciones t
            WHERE " . implode(' AND ', $where) . "
        ";

        $totalRows = $this->db->exec($totalSql, $params);
        $totalExpenses = 0.0;
        if (is_array($totalRows) && isset($totalRows[0]['total_expenses'])) {
            $totalExpenses = round((float)$totalRows[0]['total_expenses'], 2);
        }

        $sql = "
            SELECT
                t.categoria_id AS category_id,
                COALESCE(c.nombre, 'Sin categoría') AS category_name,
                COALESCE(SUM(t.monto), 0) AS total,
                COUNT(*) AS transactions_count
            FROM transacciones t
            LEFT JOIN categorias c ON c.id = t.categoria_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY t.categoria_id, COALESCE(c.nombre, 'Sin categoría')
            ORDER BY total DESC
        ";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $rows = $this->db->exec($sql, $params);
        if (!is_array($rows)) {
            return [
                'total_expenses' => 0.0,
                'items' => []
            ];
        }

        $items = [];
        foreach ($rows as $row) {
            $total = round((float)$row['total'], 2);

            $items[] = [
                'category_id' => $row['category_id'] !== null ? (int)$row['category_id'] : null,
                'category_name' => (string)$row['category_name'],
                'total' => $total,
                'percentage' => $totalExpenses > 0
                    ? round(($total / $totalExpenses) * 100, 2)
                    : 0.0,
                'transactions_count' => (int)$row['transactions_count']
            ];
        }

        return [
            'total_expenses' => $totalExpenses,
            'items' => $items
        ];
    }

    private function buildFiltersPayload(string $fechaInicio, string $fechaFin, array $accountIds): array
    {
        return [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'cuentas' => array_values($accountIds)
        ];
    }

    private function appendAccountFilter(array &$where, array &$params, string $column, array $accountIds): void
    {
        if (empty($accountIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $where[] = $column . " IN ($placeholders)";

        foreach ($accountIds as $accountId) {
            $params[] = (int)$accountId;
        }
    }

    private function generateMonthlyPeriods(string $fechaInicio, string $fechaFin): array
    {
        $periods = [];
        $current = new \DateTimeImmutable(substr($fechaInicio, 0, 7) . '-01');
        $end = new \DateTimeImmutable(substr($fechaFin, 0, 7) . '-01');

        while ($current <= $end) {
            $periods[] = [
                'period' => $current->format('Y-m'),
                'date' => $current
            ];

            $current = $current->modify('+1 month');
        }

        return $periods;
    }

    private function buildIncomeExpenseItems(array $periods, array $monthlyMap, bool $multipleYears): array
    {
        $items = [];

        foreach ($periods as $period) {
            $periodKey = $period['period'];
            $income = isset($monthlyMap[$periodKey]) ? $monthlyMap[$periodKey]['income'] : 0.0;
            $expenses = isset($monthlyMap[$periodKey]) ? $monthlyMap[$periodKey]['expenses'] : 0.0;

            $items[] = [
                'period' => $periodKey,
                'month' => $this->formatMonthLabel($period['date'], $multipleYears),
                'income' => $income,
                'expenses' => $expenses,
                'balance' => round($income - $expenses, 2)
            ];
        }

        return $items;
    }

    private function spansMultipleYears(string $fechaInicio, string $fechaFin): bool
    {
        return substr($fechaInicio, 0, 4) !== substr($fechaFin, 0, 4);
    }

    private function formatMonthLabel(\DateTimeImmutable $date, bool $includeYear): string
    {
        $monthName = $this->getSpanishMonthNames()[(int)$date->format('n')];
        return $includeYear ? $monthName . ' ' . $date->format('Y') : $monthName;
    }

    private function resolveBudgetStatus(float $progress): string
    {
        if ($progress > 100.0) {
            return 'overspent';
        }

        if ($progress === 100.0) {
            return 'limit';
        }

        if ($progress >= 70.0) {
            return 'warning';
        }

        return 'safe';
    }

    private function getSpanishMonthNames(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];
    }

    private function getTransactionDateColumn(): string
    {
        if ($this->transactionDateColumn !== null) {
            return $this->transactionDateColumn;
        }

        $fecha = $this->db->exec("SHOW COLUMNS FROM transacciones LIKE 'fecha'");
        if (!empty($fecha)) {
            $this->transactionDateColumn = 'fecha';
            return $this->transactionDateColumn;
        }

        $fechaRegistro = $this->db->exec("SHOW COLUMNS FROM transacciones LIKE 'fecha_registro'");
        if (!empty($fechaRegistro)) {
            $this->transactionDateColumn = 'fecha_registro';
            return $this->transactionDateColumn;
        }

        $this->transactionDateColumn = 'fecha_registro';
        return $this->transactionDateColumn;
    }
}