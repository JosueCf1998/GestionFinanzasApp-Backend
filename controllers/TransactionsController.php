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
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $this->transactionModel->set('usuario_id', $decoded->data->user_id);
        $this->transactionModel->set('categoria_id', $body['categoria_id']);
        $this->transactionModel->set('cuenta_id', $body['cuenta_id']);
        $this->transactionModel->set('fecha', $body['fecha']);
        $this->transactionModel->set('monto', $body['monto']);
        $this->transactionModel->set('tipo', $body['tipo']);
        $this->transactionModel->set('descripcion', $body['descripcion']);

        if ($this->transactionModel->save()) {
            $this->successResponse([
                'mensaje' => 'Transacción creada correctamente',
                'info' => [
                    'id' => $this->transactionModel->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la transacción', 500);
        }
    }

    public function update($f3)
    {
        
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $transac_id = $body['transac_id'];
        $this->transactionModel->load(['id = ? AND usuario_id = ?', $transac_id, $decoded->data->user_id]);

        if (!$this->transactionModel->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta transacción o no existe', 403);
            return;
        }
        $this->transactionModel->set('categoria_id', $body['categoria_id']);
        $this->transactionModel->set('cuenta_id', $body['cuenta_id']);
        $this->transactionModel->set('fecha', $body['fecha']);
        $this->transactionModel->set('monto', $body['monto']);
        $this->transactionModel->set('tipo', $body['tipo']);
        $this->transactionModel->set('descripcion', $body['descripcion']);

        $this->transactionModel->save();

        $this->successResponse([
            'mensaje' => 'Transacción actualizada',
            'info' => ['id' => $this->transactionModel->get('id')]
        ]);
    }

    public function delete($f3)
    {
        
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }
        
        $transac_id = $body['transac_id'];

        $this->transactionModel->load(['id = ? AND usuario_id = ?', $transac_id, $decoded->data->user_id]);

        if ($this->transactionModel->loaded()) {
            $this->transactionModel->erase();
            $this->successResponse([
                'mensaje' => 'Transacción eliminada',
                'info' => ['id' => $transac_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta transacción o no existe', 403);
        }
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);

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

        $type = strtolower(trim(
            (string)(
                $body['type']
                ?? $body['tipo']
                ?? ''
            )
        ));

        if (
            $type !== ''
            && !in_array($type, ['gasto', 'ingreso'], true)
        ) {
            $this->errorResponse(
                'Tipo inválido. Valores permitidos: gasto, ingreso',
                400
            );
            return;
        }

        if (
            empty($accountIds)
            && empty($categoryIds)
            && $type === ''
        ) {
            $this->errorResponse(
                'Debe enviar al menos una cuenta, una categoría o un tipo para filtrar',
                400
            );
            return;
        }

        if (!empty($accountIds)) {
            $validAccountIds = $this->getValidFilterAccountIds(
                $accountIds,
                $userId
            );

            if (count($validAccountIds) !== count($accountIds)) {
                $this->errorResponse(
                    'Una o más cuentas no existen o no pertenecen al usuario',
                    400
                );
                return;
            }
        }

        if (!empty($categoryIds)) {
            $validCategoryIds = $this->getValidFilterCategoryIds(
                $categoryIds,
                $userId
            );

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
                t.monto,
                t.tipo,
                t.fecha_registro,
                t.descripcion
            FROM transacciones t
            INNER JOIN cuentas a ON a.id = t.cuenta_id
            INNER JOIN categorias c ON c.id = t.categoria_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.fecha_registro DESC,
                     t.id DESC
        ";

        $rows = $db->exec($sql, $params);

        $accountMap = [];
        $categoryMap = [];
        $expensesList = [];
        $incomeList = [];
        $totalAmount = 0.0;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                $amount = round((float)$row['monto'], 2);
                $transactionType = strtolower(trim((string)$row['tipo']));

                $totalAmount += $amount;

                $accountId = (int)$row['cuenta_id'];

                if (!isset($accountMap[$accountId])) {
                    $accountMap[$accountId] = [
                        'color' => $row['cuenta_color'],
                        'icon' => $row['cuenta_icono'],
                        'id' => $accountId,
                        'name' => $row['cuenta']
                        
                        
                    ];
                }

                $categoryId = (int)$row['categoria_id'];

                if (!isset($categoryMap[$categoryId])) {
                    $categoryMap[$categoryId] = [
                        'category' => [
                            'id' => $categoryId
                        ],
                        'amount' => 0.0
                    ];
                }

                $categoryMap[$categoryId]['amount'] += $amount;

                $transaction = [
                    'id' => (int)$row['id'],
                    'account' => [
                        'id' => $accountId,
                        'name' => $row['cuenta'],
                        'icon' => $row['cuenta_icono'],
                        'color' => $row['cuenta_color']
                    ],
                    'category' => [
                        'id' => $categoryId,
                        'name' => $row['categoria'],
                        'icon' => $row['categoria_icono'],
                        'color' => $row['categoria_color']
                    ],
                    'amount' => number_format($amount, 2, '.', ''),
                    'type' => $transactionType === 'gasto'
                        ? 'expense'
                        : 'income',
                    'date' => $row['fecha_registro'],
                    'createdAt' => $row['fecha_registro'],
                    'description' => $row['descripcion']
                ];

                if ($transactionType === 'ingreso') {
                    $incomeList[] = $transaction;
                } elseif ($transactionType === 'gasto') {
                    $expensesList[] = $transaction;
                }
            }
        }

        $accountList = array_values($accountMap);

        $grafitcategory = [];

        foreach ($categoryMap as $categoryData) {
            $categoryAmount = round((float)$categoryData['amount'], 2);

            $percentage = $totalAmount > 0
                ? round(($categoryAmount / $totalAmount) * 100, 2)
                : 0.00;

            $grafitcategory[] = [
                'category' => $categoryData['category'],
                'amount' => number_format($categoryAmount, 2, '.', ''),
                'percentage' => number_format($percentage, 2, '.', '')
            ];
        }

        usort(
            $grafitcategory,
            function ($a, $b) {
                return (float)$b['percentage'] <=> (float)$a['percentage'];
            }
        );

        $transactionCount = count($expensesList) + count($incomeList);

        $this->successResponse([
            'accountList' => $accountList,
            'totalAccount' => count($accountList),
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
            'grafitcategory' => $grafitcategory,
            'transactionList' => [
                'expensesList' => $expensesList,
                'incomeList' => $incomeList
            ],
            'message' => $transactionCount > 0
                ? 'Transacciones filtradas correctamente'
                : 'No se encontraron transacciones'
        ]);
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