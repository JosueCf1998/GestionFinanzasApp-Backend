<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class CategoriesController extends BaseController
{
    protected $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new \m_categorias();
    }

    // Wrapper to match routes.ini (POST /categories/register)
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

        $this->categoryModel->set('usuario_id', $decoded->data->user_id);
        $this->categoryModel->set('nombre', $body['nombre']);
        $this->categoryModel->set('tipo', $body['tipo']);
        $this->categoryModel->set('icono', $body['icono']);
        $this->categoryModel->set('color', $body['color']);
     
        if ($this->categoryModel->save()) {
            $this->successResponse([
                'mensaje' => 'Categoría creada correctamente',
                'info' => [
                    'id' => $this->categoryModel->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la categoría', 500);
        }
    }

    public function update($f3)
    {   
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $categoria_id = $body['categoria_id'] ?? $body['id'] ?? $f3->get('PARAMS.categoria_id');
        
        if (!$categoria_id) {
            $this->errorResponse('ID de categoría no proporcionado', 400);
            return;
        }

        $nombre = $body['nombre'] ?? $body['name'] ?? null;
        $tipo = $body['tipo'] ?? $body['type'] ?? null;
        $icono = $body['icono'] ?? $body['icon'] ?? null;
        $color = $body['color'] ?? null;

        if (!$nombre || !$tipo || !$icono || !$color) {
            $this->errorResponse('Faltan datos requeridos', 400);
            return;
        }

        $this->categoryModel->load(['id = ? AND usuario_id = ?', $categoria_id, $decoded->data->user_id]);

        if (!$this->categoryModel->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta categoría o no existe', 403);
            return;
        }

        $_category = new \m_categorias();
        $_category->load(['nombre = ? AND id <> ? AND usuario_id = ?', $nombre, $categoria_id, $decoded->data->user_id]);

        if ($_category->loaded()) {
            $this->errorResponse('El nombre ya está en uso por otra categoría tuya', 409);
            return;
        }

        $this->categoryModel->set('nombre', $nombre);
        $this->categoryModel->set('tipo', $tipo);
        $this->categoryModel->set('icono', $icono);
        $this->categoryModel->set('color', $color);

        $this->categoryModel->save();

        $this->successResponse([
            'mensaje' => 'Categoría actualizada',
            'info' => ['id' => $this->categoryModel->get('id')]
        ]);
    }

    public function delete($f3)
    {
        $decoded = $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $categoria_id = $body['categoria_id'];
        $this->categoryModel->load(['id = ? AND usuario_id = ?', $categoria_id, $decoded->data->user_id]);

        if ($this->categoryModel->loaded() > 0) {
            $this->categoryModel->erase();
            $this->successResponse([
                'mensaje' => 'Categoría eliminada',
                'info' => ['id' => $categoria_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta categoría o no existe', 403);
        }
    }

    public function list($f3)
    {
        $decoded = $this->requireAuth($f3);
        $result = $this->categoryModel->find(['usuario_id = ? OR usuario_id IS NULL', $decoded->data->user_id]);
        $items = [];
        foreach ($result as $categoria) {
            $items[] = $categoria->cast();
        }

        if (count($items) > 0) {
            $this->successResponse(['items' => $items, 'Total' => count($items)]);
        } else {
            $this->errorResponse('Aún no hay registros que mostrar', 404, ['items' => [], 'Total' => 0]);
        }
    }

    // CAMBIO: endpoint especifico para modulo de presupuestos.
    // Devuelve categorias de gasto (globales + del usuario) para los multi-selects.
    public function budgetList($f3)
    {
        $decoded = $this->requireAuth($f3);
        $result = $this->categoryModel->find([
            '(usuario_id = ? OR usuario_id IS NULL) AND LOWER(COALESCE(tipo, "")) IN ("gasto", "expense", "egreso")',
            $decoded->data->user_id
        ]);

        $items = [];
        foreach ($result as $categoria) {
            $items[] = $categoria->cast();
        }

        $this->successResponse([
            'items' => $items,
            'Total' => count($items),
            'mensaje' => 'Listado de categorías para presupuestos'
        ]);
    }
}
