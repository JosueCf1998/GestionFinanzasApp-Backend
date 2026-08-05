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

    public function getSummary(int $userId, int $year, int $month, string $startDate, string $endDate): array
    {
        $period = [
            'year' => $year,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $monthlyTotals = $this->getIncomeExpenseTotalsByRange($userId, $startDate, $endDate);
        $budgetProgress = $this->calculateBudgetProgressData($userId, $startDate, $endDate);
        $currentBalance = $this->getCurrentBalance($userId);

        $totalIncome = $monthlyTotals['income'];
        $totalExpenses = $monthlyTotals['expenses'];
        $monthlyBalance = round($totalIncome - $totalExpenses, 2);
        $totalBudget = $budgetProgress['totals']['budgeted'];
        $budgetSpent = $budgetProgress['totals']['spent'];
        $budgetRemaining = round($totalBudget - $budgetSpent, 2);
        $savingsRate = $totalIncome > 0
            ? round(($monthlyBalance / $totalIncome) * 100, 2)
            : 0.0;

        return [
            'period' => $period,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'monthly_balance' => $monthlyBalance,
                'current_balance' => $currentBalance,
                'total_budget' => $totalBudget,
                'budget_spent' => $budgetSpent,
                'budget_remaining' => $budgetRemaining,
                'savings_rate' => $savingsRate
            ]
        ];
    }

    public function getExpensesByCategory(int $userId, int $year, int $month, string $startDate, string $endDate): array
    {
        $stats = $this->getExpenseCategoryStats($userId, $startDate, $endDate, null);

        return [
            'period' => [
                'year' => $year,
                'month' => $month
            ],
            'total_expenses' => $stats['total_expenses'],
            'items' => $stats['items']
        ];
    }

    public function getIncomeVsExpenses(int $userId, int $year): array
    {
        $monthlyMap = $this->getIncomeVsExpenseMapByYear($userId, $year);
        $months = $this->getSpanishMonthNames();

        $items = [];
        $totalIncome = 0.0;
        $totalExpenses = 0.0;

        for ($month = 1; $month <= 12; $month++) {
            $income = isset($monthlyMap[$month]) ? $monthlyMap[$month]['income'] : 0.0;
            $expenses = isset($monthlyMap[$month]) ? $monthlyMap[$month]['expenses'] : 0.0;
            $balance = round($income - $expenses, 2);

            $items[] = [
                'month_number' => $month,
                'month' => $months[$month],
                'income' => $income,
                'expenses' => $expenses,
                'balance' => $balance
            ];

            $totalIncome += $income;
            $totalExpenses += $expenses;
        }

        $totalIncome = round($totalIncome, 2);
        $totalExpenses = round($totalExpenses, 2);

        return [
            'year' => $year,
            'items' => $items,
            'totals' => [
                'income' => $totalIncome,
                'expenses' => $totalExpenses,
                'balance' => round($totalIncome - $totalExpenses, 2)
            ]
        ];
    }

    public function getBalanceEvolution(int $userId, int $year): array
    {
        $yearStart = sprintf('%04d-01-01', $year);
        $monthlyMap = $this->getIncomeVsExpenseMapByYear($userId, $year);
        $months = $this->getSpanishMonthNames();

        // Fuente de verdad elegida: saldos actuales de cuentas + movimientos históricos.
        // Se reconstruye el saldo inicial del año restando los movimientos posteriores
        // al inicio del año al saldo actual, evitando doble conteo.
        $currentBalance = $this->getCurrentBalance($userId);
        $netAfterYearStart = $this->getNetMovementFromDate($userId, $yearStart);
        $initialBalance = round($currentBalance - $netAfterYearStart, 2);

        $items = [];
        $accumulated = $initialBalance;

        for ($month = 1; $month <= 12; $month++) {
            $income = isset($monthlyMap[$month]) ? $monthlyMap[$month]['income'] : 0.0;
            $expenses = isset($monthlyMap[$month]) ? $monthlyMap[$month]['expenses'] : 0.0;
            $monthlyBalance = round($income - $expenses, 2);
            $accumulated = round($accumulated + $monthlyBalance, 2);

            $items[] = [
                'month_number' => $month,
                'month' => $months[$month],
                'income' => $income,
                'expenses' => $expenses,
                'monthly_balance' => $monthlyBalance,
                'accumulated_balance' => $accumulated
            ];
        }

        return [
            'year' => $year,
            'initial_balance' => $initialBalance,
            'items' => $items
        ];
    }

    public function getBudgetProgress(int $userId, int $year, int $month, string $startDate, string $endDate): array
    {
        $result = $this->calculateBudgetProgressData($userId, $startDate, $endDate);

        return [
            'period' => [
                'year' => $year,
                'month' => $month
            ],
            'totals' => $result['totals'],
            'items' => $result['items']
        ];
    }

    public function getTopExpenseCategories(int $userId, int $year, int $month, string $startDate, string $endDate, int $limit): array
    {
        $stats = $this->getExpenseCategoryStats($userId, $startDate, $endDate, $limit);
        $items = [];
        $position = 1;

        foreach ($stats['items'] as $item) {
            $item['position'] = $position;
            $items[] = $item;
            $position++;
        }

        return [
            'period' => [
                'year' => $year,
                'month' => $month
            ],
            'limit' => $limit,
            'items' => $items
        ];
    }

    private function getIncomeExpenseTotalsByRange(int $userId, string $startDate, string $endDate): array
    {
        $dateColumn = $this->getTransactionDateColumn();

        $sql = "
            SELECT
                COALESCE(SUM(CASE
                    WHEN LOWER(COALESCE(t.tipo, '')) IN ('ingreso', 'income')
                    THEN t.monto ELSE 0
                END), 0) AS total_income,
                COALESCE(SUM(CASE
                    WHEN LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso')
                    THEN t.monto ELSE 0
                END), 0) AS total_expenses
            FROM transacciones t
            WHERE t.usuario_id = ?
              AND DATE(t.$dateColumn) BETWEEN ? AND ?
        ";

        $rows = $this->db->exec($sql, [$userId, $startDate, $endDate]);
        $row = is_array($rows) && isset($rows[0]) ? $rows[0] : [];

        return [
            'income' => round((float)($row['total_income'] ?? 0), 2),
            'expenses' => round((float)($row['total_expenses'] ?? 0), 2)
        ];
    }

    private function getCurrentBalance(int $userId): float
    {
        $rows = $this->db->exec(
            "SELECT COALESCE(SUM(saldo), 0) AS total_balance FROM cuentas WHERE usuario_id = ?",
            [$userId]
        );

        if (!is_array($rows) || !isset($rows[0]['total_balance'])) {
            return 0.0;
        }

        return round((float)$rows[0]['total_balance'], 2);
    }

    private function getIncomeVsExpenseMapByYear(int $userId, int $year): array
    {
        $dateColumn = $this->getTransactionDateColumn();

        $sql = "
            SELECT
                MONTH(DATE(t.$dateColumn)) AS month_number,
                COALESCE(SUM(CASE
                    WHEN LOWER(COALESCE(t.tipo, '')) IN ('ingreso', 'income')
                    THEN t.monto ELSE 0
                END), 0) AS income,
                COALESCE(SUM(CASE
                    WHEN LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso')
                    THEN t.monto ELSE 0
                END), 0) AS expenses
            FROM transacciones t
            WHERE t.usuario_id = ?
              AND YEAR(DATE(t.$dateColumn)) = ?
            GROUP BY MONTH(DATE(t.$dateColumn))
            ORDER BY MONTH(DATE(t.$dateColumn)) ASC
        ";

        $rows = $this->db->exec($sql, [$userId, $year]);
        $result = [];

        if (!is_array($rows)) {
            return $result;
        }

        foreach ($rows as $row) {
            $monthNumber = (int)$row['month_number'];
            $result[$monthNumber] = [
                'income' => round((float)$row['income'], 2),
                'expenses' => round((float)$row['expenses'], 2)
            ];
        }

        return $result;
    }

    private function getNetMovementFromDate(int $userId, string $startDate): float
    {
        $dateColumn = $this->getTransactionDateColumn();

        $sql = "
            SELECT COALESCE(SUM(
                CASE
                    WHEN LOWER(COALESCE(t.tipo, '')) IN ('ingreso', 'income') THEN t.monto
                    WHEN LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso') THEN -t.monto
                    ELSE 0
                END
            ), 0) AS net_amount
            FROM transacciones t
            WHERE t.usuario_id = ?
              AND DATE(t.$dateColumn) >= ?
        ";

        $rows = $this->db->exec($sql, [$userId, $startDate]);
        if (!is_array($rows) || !isset($rows[0]['net_amount'])) {
            return 0.0;
        }

        return round((float)$rows[0]['net_amount'], 2);
    }

    private function calculateBudgetProgressData(int $userId, string $startDate, string $endDate): array
    {
        $budgetRows = $this->getActiveBudgetRowsBySeries($userId, $startDate, $endDate);

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

        $spentByBudget = $this->getSpentByBudgetIds($budgetIds, $startDate, $endDate);
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

            $status = $this->resolveBudgetStatus($progress);
            $isOverspent = $progress > 100.0;

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
                'status' => $status,
                'is_overspent' => $isOverspent
            ];

            $totalBudgeted += $budgeted;
            $totalSpent += $spent;
        }

        $totalBudgeted = round($totalBudgeted, 2);
        $totalSpent = round($totalSpent, 2);
        $totalRemaining = round($totalBudgeted - $totalSpent, 2);
        $usage = $totalBudgeted > 0
            ? round(($totalSpent / $totalBudgeted) * 100, 2)
            : 0.0;

        return [
            'totals' => [
                'budgeted' => $totalBudgeted,
                'spent' => $totalSpent,
                'remaining' => $totalRemaining,
                'usage_percentage' => $usage
            ],
            'items' => $items
        ];
    }

    private function getActiveBudgetRowsBySeries(int $userId, string $startDate, string $endDate): array
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
            ORDER BY b.start_date ASC, b.id ASC
        ";

        $rows = $this->db->exec($sql, [$userId, $endDate, $startDate, $userId]);

        return is_array($rows) ? $rows : [];
    }

    private function getSpentByBudgetIds(array $budgetIds, string $startDate, string $endDate): array
    {
        if (empty($budgetIds)) {
            return [];
        }

        $dateColumn = $this->getTransactionDateColumn();
        $placeholders = implode(',', array_fill(0, count($budgetIds), '?'));

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
               AND LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso')
               AND DATE(t.$dateColumn) BETWEEN GREATEST(b.start_date, ?) AND LEAST(b.end_date, ?)
            WHERE b.id IN ($placeholders)
            GROUP BY b.id
        ";

        $params = [$startDate, $endDate];
        foreach ($budgetIds as $budgetId) {
            $params[] = (int)$budgetId;
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
            $totalCategories = (int)$row['total_categories'];

            if ($totalCategories === 1) {
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

    private function getExpenseCategoryStats(int $userId, string $startDate, string $endDate, ?int $limit): array
    {
        $dateColumn = $this->getTransactionDateColumn();

        $totalSql = "
            SELECT COALESCE(SUM(t.monto), 0) AS total_expenses
            FROM transacciones t
            WHERE t.usuario_id = ?
              AND LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso')
              AND DATE(t.$dateColumn) BETWEEN ? AND ?
        ";

        $totalRows = $this->db->exec($totalSql, [$userId, $startDate, $endDate]);
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
            WHERE t.usuario_id = ?
              AND LOWER(COALESCE(t.tipo, '')) IN ('gasto', 'expense', 'egreso')
              AND DATE(t.$dateColumn) BETWEEN ? AND ?
            GROUP BY t.categoria_id, COALESCE(c.nombre, 'Sin categoría')
            ORDER BY total DESC
        ";

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$limit;
        }

        $rows = $this->db->exec($sql, [$userId, $startDate, $endDate]);
        if (!is_array($rows)) {
            return [
                'total_expenses' => 0.0,
                'items' => []
            ];
        }

        $items = [];
        foreach ($rows as $row) {
            $categoryId = $row['category_id'] !== null ? (int)$row['category_id'] : null;
            $total = round((float)$row['total'], 2);
            $percentage = $totalExpenses > 0
                ? round(($total / $totalExpenses) * 100, 2)
                : 0.0;

            $items[] = [
                'category_id' => $categoryId,
                'category_name' => (string)$row['category_name'],
                'total' => $total,
                'percentage' => $percentage,
                'transactions_count' => (int)$row['transactions_count']
            ];
        }

        return [
            'total_expenses' => $totalExpenses,
            'items' => $items
        ];
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
