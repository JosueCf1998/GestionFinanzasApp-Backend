<?php

class categoria_controllers
{
    public $m_categoria = null;
    public function __construct()
    {
        $this->m_categoria = new m_categorias();
    }
    public function crear($f3)
    {
        $this->validarToken($f3);
        $this->m_categoria->set('nombre', $f3->get('POST.nombre'));
        $this->m_categoria->set('tipo', $f3->get('POST.tipo'));
        $this->m_categoria->save();
        echo json_encode([
            'mensaje' => 'Categoria creada',
            'info' => [
                'id' => $this->m_categoria->get('id')
            ]
        ]);
    }
    public function actualizar($f3)
    {
        $categoria_id = $f3->get('PARAMS.categoria_id');
        $this->m_categoria->load(['id = ?', $categoria_id]);
        $msg = '';
        if ($this->m_categoria->loaded() > 0) {
            $_categoria = new m_categorias();
            $_categoria->load(['nombre = ? AND id <> ?', $f3->get('POST.nombre'), $categoria_id]);
            if ($_categoria->loaded() > 0) {
                $msg = 'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra categoria';
            } else {
                $this->m_categoria->set('nombre', $f3->get('POST.nombre'));
                $this->m_categoria->set('tipo', $f3->get('POST.tipo'));
                $this->m_categoria->save();
                $msg = 'Categoria actualizada';
            }
        } else {
            $msg = 'Categoria no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }
    public function consultar($f3)
    {
        $categoria_id = $f3->get('PARAMS.categoria_id');
        $this->m_categoria->load(['id = ?', $categoria_id]);
        $msg = '';
        $items = array();
        if ($this->m_categoria->loaded() > 0) {
            $msg = 'Categoria encontrada';
            $items = $this->m_categoria->cast();
        } else {
            $msg = 'Categoria no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => [
                'items' => $items
            ]
        ]);
    }
    private function validarToken($f3)
    {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            echo json_encode(['mensaje' => 'Token no proporcionado']);
            http_response_code(401);
            exit;
        }

        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
            try {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('$#Gre1410#$', 'HS256'));
                $f3->set('user_id', $decoded->data->user_id);
            } catch (Exception $e) {
                echo json_encode(['mensaje' => 'Token inválido o expirado']);
                http_response_code(403);
                exit;
            }
        } else {
            echo json_encode(['mensaje' => 'Formato de token inválido']);
            http_response_code(400);
            exit;
        }
    }

    public function eliminar($f3)
    {
        $categoria_id = $f3->get('POST.categoria_id');
        $this->m_categoria->load(['id = ?', $categoria_id]);
        $msg = '';
        if ($this->m_categoria->loaded() > 0) {
            $msg = 'Categoria eliminada';
            $this->m_categoria->erase();
        } else {
            $msg = 'Categoria no encontrada';
        }
        echo json_encode([
            'mensaje' => $msg,
            'info' => []
        ]);
    }
    public function listado($f3)
    {
        $result = $this->m_categoria->find();
        foreach ($result as $categoria) {
            $items[] = $categoria->cast();
        }
        echo json_encode([
            'mensaje' => count($items) > 0 ? '' : 'aun no hay registros que mostrar',
            'info' => [
                'items' => $items,
                'Total' => count($items),
            ]
        ]);
    }
}
