<?php

require_once 'BaseController.php';
require_once 'helpers/ResponseHelper.php';
require_once 'helpers/SecurityHelper.php';
require_once 'helpers/JwtHelper.php';
require_once 'helpers/SessionHelper.php';
require_once 'helpers/AesDecryptor.php';

class categoria_controllers extends BaseController
{
    protected $m_categoria;
    private $jwtKey;

    public function __construct()
    {
        $this->m_categoria = new m_categorias();
        $this->jwtKey = getenv('JWT_SECRET') ?: '$#Gre1410#$';
    }

    public function crear($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey); 
        $body = json_decode($f3->get('BODY'), true);

        $this->m_categoria->set('usuario_id', $decoded->data->user_id);
        $this->m_categoria->set('nombre', $body['nombre']);
        $this->m_categoria->set('tipo', $body['tipo']);
        $this->m_categoria->set('icono', $body['icono']);
        $this->m_categoria->set('color', $body['color']);
     
        if ($this->m_categoria->save()) {
            $this->successResponse([
                'mensaje' => 'Categoría creada correctamente',
                'info' => [
                    'id' => $this->m_categoria->get('id')
                ]
            ]);
        } else {
            $this->errorResponse('No se pudo crear la categoría', 500);
        }
    }

    public function actualizar($f3)
    {   
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $categoria_id = $f3->get('PARAMS.categoria_id');
        // Solo puede actualizar si es dueño y no es global
        $this->m_categoria->load(['id = ? AND usuario_id = ?', $categoria_id, $decoded->data->user_id]);

        if (!$this->m_categoria->loaded()) {
            $this->errorResponse('No tienes permiso para actualizar esta categoría o no existe', 403);
            return;
        }

        $body = json_decode($f3->get('BODY'), true);

        $_categoria = new m_categorias();
        $_categoria->load(['nombre = ? AND id <> ? AND usuario_id = ?', $body['nombre'], $categoria_id, $decoded->data->user_id]);

        if ($_categoria->loaded()) {
            $this->errorResponse('El nombre ya está en uso por otra categoría tuya', 409);
            return;
        }

        $this->m_categoria->set('nombre', $body['nombre']);
        $this->m_categoria->set('tipo', $body['tipo']);
        $this->m_categoria->set('icono', $body['icono']);
        $this->m_categoria->set('color', $body['color']);

        $this->m_categoria->save();

        $this->successResponse([
            'mensaje' => 'Categoría actualizada',
            'info' => ['id' => $this->m_categoria->get('id')]
        ]);
    }

    public function eliminar($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $body = json_decode($f3->get('BODY'), true);
        $categoria_id = $body['categoria_id'];

        // Solo puede eliminar si es dueño y no es global
        $this->m_categoria->load(['id = ? AND usuario_id = ?', $categoria_id, $decoded->data->user_id]);

        if ($this->m_categoria->loaded() > 0) {
            $this->m_categoria->erase();
            $this->successResponse([
                'mensaje' => 'Categoría eliminada',
                'info' => ['id' => $categoria_id]
            ]);
        } else {
            $this->errorResponse('No tienes permiso para eliminar esta categoría o no existe', 403);
        }
    }

    public function listado($f3)
    {
        $token = JwtHelper::getBearerToken($f3);
        $decoded = JwtHelper::validateToken($token, $this->jwtKey);
        $result = $this->m_categoria->find(['usuario_id = ? OR usuario_id IS NULL', $decoded->data->user_id]);
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
}
