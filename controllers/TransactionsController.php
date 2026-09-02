<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class TransactionsController extends BaseController
{
    protected $transactionModel;

    public function __construct()
    {
        parent::__construct();
        $this->transactionModel = new \m_transacciones();
    }

    // Wrapper to match routes.ini (POST /transactions/register)
    public function register($f3)
    {
        return $this->create($f3);
    }

    public function create($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        try {
            $fields = $this->normalizeTransactionFields($body);
            $this->validateTransactionRelations($fields, (int)$decoded->data->user_id);
        } catch (\InvalidArgumentException $exception) {
            $this->errorResponse($exception->getMessage(), 400);
            return;
        }

        $this->transactionModel->set('usuario_id', $decoded->data->user_id);
        $this->assignTransactionFields($fields);

        if ($this->transactionModel->save()) {
            $this->successResponse([
                'mensaje' => 'Transacción creada correctamente',
                'info' => [
                    'id' => (int)$this->transactionModel->get('id'),
                    'date' => $this->transactionModel->get('fecha')
                ]
            ]);
            return;
        }

        $this->errorResponse('No se pudo crear la transacción', 500);
    }

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

        $transacId = (int)($body['transac_id'] ?? 0);
        if ($transacId <= 0) {
            $this->errorResponse('ID de transacción inválido', 400);
            return;
        }

        $this->transactionModel->load(['id = ? AND usuario_id = ?', $transacId, $decoded->data->user_id]);

        if (!$this->transactionModel->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta transacción o no existe', 403);
            return;
        }

        try {
            $fields = $this->normalizeTransactionFields($body);
            $this->validateTransactionRelations($fields, (int)$decoded->data->user_id);
        } catch (\InvalidArgumentException $exception) {
            $this->errorResponse($exception->getMessage(), 400);
            return;
        }

        $this->assignTransactionFields($fields);

        if (!$this->transactionModel->save()) {
            $this->errorResponse('No se pudo actualizar la transacción', 500);
            return;
        }

        $this->successResponse([
                'mensaje' => 'Transacción actualizada',
                'info' => [
                    'id' => (int)$this->transactionModel->get('id'),
                    'date' => $this->transactionModel->get('fecha')
            ]
        ]);
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

        $transacId = (int)($body['transac_id'] ?? 0);
        if ($transacId <= 0) {
            $this->errorResponse('ID de transacción inválido', 400);
            return;
        }

        $this->transactionModel->load(['id = ? AND usuario_id = ?', $transacId, $decoded->data->user_id]);

        if ($this->transactionModel->loaded()) {
            $this->transactionModel->erase();
            $this->successResponse([
                'mensaje' => 'Transacción eliminada',
                'info' => [
                    'id' => $transacId
                ]
            ]);
            return;
        }

        $this->errorResponse('No tienes permiso para eliminar esta transacción o no existe', 403);
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);
        if (!$decoded) {
            return;
        }

        $result = $this->transactionModel->find(['usuario_id = ?', $decoded->data->user_id]);
        $items = [];

        foreach ($result as $transaccion) {
            $items[] = $transaccion->cast();
        }

        $this->successResponse([
            'items' => $items,
            'Total' => count($items),
            'mensaje' => count($items) > 0
                ? 'Listado obtenido correctamente'
                : 'No hay registros que mostrar'
        ]);
    }

    /**
     * POST /transactions/filter
     * Filtra por una o varias cuentas, una o varias categorías y por tipo.
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

        $accountIds = $this->normalizeFilterIds(
            $body['account_ids']
                ?? $body['cuenta_ids']
                ?? $body['account_id']
                ?? $body['cuenta_id']
                ?? []
        );

        $categoryIds = $this->normalizeFilterIds(
            $body['category_ids']
                ?? $body['categoria_ids']
                ?? $body['category_id']
                ?? $body['categoria_id']
                ?? []
        );

        $type = strtolower(trim((string)($body['type'] ?? $body['tipo'] ?? '')));

        try {
            $startDate = $this->normalizeFilterDate(
                $body['start_date'] ?? $body['fecha_inicio'] ?? null
            );
            $endDate = $this->normalizeFilterDate(
                $body['end_date'] ?? $body['fecha_fin'] ?? null
            );
        } catch (\InvalidArgumentException $exception) {
            $this->errorResponse($exception->getMessage(), 400);
            return;
        }

        if ($startDate !== null && $endDate !== null && $startDate > $endDate) {
            $this->errorResponse('El rango de fechas es inválido', 400);
            return;
        }

        if ($type === 'expense') {
            $type = 'gasto';
        } elseif ($type === 'income') {
            $type = 'ingreso';
        }

        if ($type !== '' && !in_array($type, ['gasto', 'ingreso'], true)) {
            $this->errorResponse('Tipo inválido. Valores permitidos: expense, income', 400);
            return;
        }

        if (empty($accountIds) && empty($categoryIds) && $type === '' && $startDate === null && $endDate === null) {
            $this->errorResponse(
                'Debe enviar al menos una cuenta, una categoría, un tipo o un rango de fechas para filtrar',
                400
            );
            return;
        }

        if (!empty($accountIds)) {
            $validAccountIds = $this->getValidFilterAccountIds($accountIds, $userId);
            if (count($validAccountIds) !== count($accountIds)) {
                $this->errorResponse(
                    'Una o más cuentas no existen o no pertenecen al usuario',
                    400
                );
                return;
            }
        }

        if (!empty($categoryIds)) {
            $validCategoryIds = $this->getValidFilterCategoryIds($categoryIds, $userId);
            if (count($validCategoryIds) !== count($categoryIds)) {
                $this->errorResponse(
                    'Una o más categorías no existen o no están disponibles para el usuario',
                    400
                );
                return;
            }
        }

        $where = ['t.usuario_id = ?'];
        $params = [$userId];

        if (!empty($accountIds)) {
            $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
            $where[] = "t.cuenta_id IN ($placeholders)";
            foreach ($accountIds as $accountId) {
                $params[] = $accountId;
            }
        }

        if (!empty($categoryIds)) {
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $where[] = "t.categoria_id IN ($placeholders)";
            foreach ($categoryIds as $categoryId) {
                $params[] = $categoryId;
            }
        }

        if ($type !== '') {
            $where[] = 'LOWER(TRIM(t.tipo)) = ?';
            $params[] = $type;
        }

        if ($startDate !== null) {
            $where[] = 't.fecha >= ?';
            $params[] = $startDate;
        }

        if ($endDate !== null) {
            $where[] = 't.fecha <= ?';
            $params[] = $endDate;
        }

        $db = \Base::instance()->get('DB');

        $sql = "
            SELECT
                t.id,
                t.usuario_id,
                t.categoria_id,
                c.nombre AS categoria,
                c.icono AS categoria_icono,
                c.color AS categoria_color,
                t.cuenta_id,
                a.nombre AS cuenta,
                a.icon AS cuenta_icono,
                a.color AS cuenta_color,
                a.saldo AS cuenta_saldo,
                t.monto,
                t.tipo,
                t.fecha,
                t.fecha_registro,
                t.descripcion
            FROM transacciones t
            INNER JOIN cuentas a ON a.id = t.cuenta_id
            INNER JOIN categorias c ON c.id = t.categoria_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.fecha DESC, t.id DESC
        ";

        $rows = $db->exec($sql, $params);

        $expensesList = [];
        $incomeList = [];
        $totalAmount = 0.0;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $transaction = $this->mapTransactionRow($row);
                $amount = round((float)$transaction['amount'], 2);
                $totalAmount += $amount;

                if ($transaction['type'] === 'income') {
                    $incomeList[] = $transaction;
                } else {
                    $expensesList[] = $transaction;
                }
            }
        }

        $transactionCount = count($expensesList) + count($incomeList);

        $this->successResponse([
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
            'transactionList' => [
                'expensesList' => $expensesList,
                'incomeList' => $incomeList
            ],
            'message' => $transactionCount > 0
                ? 'Transacciones filtradas correctamente'
                : 'No se encontraron transacciones'
        ]);
    }

    protected function assignTransactionFields(array $body): void
    {
        $this->transactionModel->set('categoria_id', $body['categoria_id'] ?? null);
        $this->transactionModel->set('cuenta_id', $body['cuenta_id'] ?? null);
        $this->transactionModel->set('fecha', $body['fecha'] ?? null);
        $this->transactionModel->set('monto', $body['monto'] ?? null);
        $this->transactionModel->set('tipo', $body['tipo'] ?? null);
        $this->transactionModel->set('descripcion', $body['descripcion'] ?? null);
    }

    protected function normalizeTransactionFields(array $body): array
    {
        $categoryId = (int)($body['categoria_id'] ?? 0);
        $accountId = (int)($body['cuenta_id'] ?? 0);
        $amount = round((float)($body['monto'] ?? 0), 2);
        $type = strtolower(trim((string)($body['tipo'] ?? '')));
        $date = $this->normalizeFilterDate($body['fecha'] ?? null);

        if ($categoryId <= 0 || $accountId <= 0) {
            throw new \InvalidArgumentException('La cuenta y la categoría son obligatorias');
        }

        if ($amount <= 0) {
            throw new \InvalidArgumentException('El monto debe ser mayor que cero');
        }

        if ($date === null) {
            throw new \InvalidArgumentException('La fecha es obligatoria');
        }

        if (!in_array($type, ['gasto', 'ingreso'], true)) {
            throw new \InvalidArgumentException('Tipo inválido. Valores permitidos: gasto, ingreso');
        }

        return [
            'categoria_id' => $categoryId,
            'cuenta_id' => $accountId,
            'fecha' => $date,
            'monto' => $amount,
            'tipo' => $type,
            'descripcion' => trim((string)($body['descripcion'] ?? ''))
        ];
    }

    protected function validateTransactionRelations(array $fields, int $userId): void
    {
        if (count($this->getValidFilterAccountIds([$fields['cuenta_id']], $userId)) !== 1) {
            throw new \InvalidArgumentException('La cuenta no existe o no pertenece al usuario');
        }

        if (count($this->getValidFilterCategoryIds([$fields['categoria_id']], $userId)) !== 1) {
            throw new \InvalidArgumentException('La categoría no existe o no está disponible para el usuario');
        }
    }

    protected function mapTransactionRow(array $row): array
    {
        $amount = round((float)$row['monto'], 2);
        $transactionType = strtolower(trim((string)$row['tipo']));

        return [
            'id' => (int)$row['id'],
            'account' => [
                'id' => (int)$row['cuenta_id'],
                'name' => $row['cuenta'],
                'icon' => $row['cuenta_icono'],
                'color' => $row['cuenta_color'],
                'amount' => number_format((float)$row['cuenta_saldo'], 2, '.', '')
            ],
            'category' => [
                'id' => (int)$row['categoria_id'],
                'name' => $row['categoria'],
                'icon' => $row['categoria_icono'],
                'color' => $row['categoria_color']
            ],
            'amount' => number_format($amount, 2, '.', ''),
            'type' => $transactionType === 'gasto' ? 'expense' : 'income',
            'date' => $row['fecha'],
            'createdAt' => $row['fecha_registro'],
            'description' => $row['descripcion']
        ];
    }

    protected function normalizeFilterIds($value): array
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

    protected function normalizeFilterDate($value): ?string
    {
        if ($value === null || trim((string)$value) === '') {
            return null;
        }

        $value = trim((string)$value);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new \InvalidArgumentException('Formato de fecha inválido. Use YYYY-MM-DD');
        }

        return $date->format('Y-m-d');
    }

    protected function getValidFilterAccountIds(array $ids, int $userId): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $userId;

        $db = \Base::instance()->get('DB');
        $rows = $db->exec(
            "SELECT id FROM cuentas WHERE id IN ($placeholders) AND usuario_id = ?",
            $params
        );

        return is_array($rows)
            ? array_map(fn($row) => (int)$row['id'], $rows)
            : [];
    }

    protected function getValidFilterCategoryIds(array $ids, int $userId): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = $ids;
        $params[] = $userId;

        $db = \Base::instance()->get('DB');
        $rows = $db->exec(
            "SELECT id FROM categorias WHERE id IN ($placeholders) AND (usuario_id = ? OR usuario_id IS NULL)",
            $params
        );

        return is_array($rows)
            ? array_map(fn($row) => (int)$row['id'], $rows)
            : [];
    }
}
