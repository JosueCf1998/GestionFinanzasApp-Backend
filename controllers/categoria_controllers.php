<?php

require_once 'BaseController.php';

class categoria_controllers extends BaseController
{
    
    public $m_categoria = null;

    public function __construct()
    {
        $this->m_categoria = new m_categorias();
    }

    // Métodos de respuesta centralizados
    

    public function crear($f3)
     {
         // $this->validarToken($f3); 
        $body = json_decode($f3->get('BODY'), true);

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
         $categoria_id = $f3->get('PARAMS.categoria_id');
         $this->m_categoria->load(['id = ?', $categoria_id]);
     
         if ($this->m_categoria->loaded() > 0) {
             $_categoria = new m_categorias();
             $_categoria->load(['nombre = ? AND id <> ?', $f3->get('POST.nombre'), $categoria_id]);
     
             if ($_categoria->loaded() > 0) {
                 $this->errorResponse(
                     'Registro no se pudo modificar debido a que el nombre se encuentra en uso por otra categoría',
                     409
                 );
             } else {
                 $body = json_decode($f3->get('BODY'), true);
                 $this->m_categoria->set('nombre', $body['nombre']);
                 $this->m_categoria->set('tipo', $body['tipo']);
                 $this->m_categoria->set('icono', $body['icono']);
                 $this->m_categoria->set('color', $body['color']);
     
                 $this->successResponse([
                     'mensaje' => 'Categoría actualizada',
                     'info' => ['id' => $this->m_categoria->get('id')]
                 ]);
             }
         } else {
             $this->errorResponse(
                 'Categoría no encontrada',
                 404
             );
         }
     }

    public function consultar($f3)
     {
         $categoria_id = $f3->get('PARAMS.categoria_id');
         $this->m_categoria->load(['id = ?', $categoria_id]);
     
         if ($this->m_categoria->loaded() > 0) {
             $this->successResponse([
                 'mensaje' => 'Categoría encontrada',
                 'info' => [
                     'items' => $this->m_categoria->cast()
                 ]
             ]);
         } else {
             $this->errorResponse(
                 'Categoría no encontrada',
                 404,
                 ['items' => []]
             );
         }
     }

    // private function validarToken($f3)
    // {
    //     $headers = getallheaders();
    //     if (!isset($headers['Authorization'])) {
    //         echo json_encode(['mensaje' => 'Token no proporcionado']);
    //         http_response_code(401);
    //         exit;
    //     }

    //     if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
    //         $token = $matches[1];
    //         try {
    //             $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('$#Gre1410#$', 'HS256'));
    //             $f3->set('user_id', $decoded->data->user_id);
    //         } catch (Exception $e) {
    //             echo json_encode(['mensaje' => 'Token inválido o expirado']);
    //             http_response_code(403);
    //             exit;
    //         }
    //     } else {
    //         echo json_encode(['mensaje' => 'Formato de token inválido']);
    //         http_response_code(400);
    //         exit;
    //     }
    // }

    public function eliminar($f3)
     {
         $categoria_id = $f3->get('POST.categoria_id');
         $this->m_categoria->load(['id = ?', $categoria_id]);
     
         if ($this->m_categoria->loaded() > 0) {
             $this->m_categoria->erase();
             $this->successResponse([
                 'mensaje' => 'Categoría eliminada',
                 'info' => ['id' => $categoria_id]
             ]);
         } else {
             $this->errorResponse(
                 'Categoría no encontrada',
                 404
             );
         }
     }

    
    
    
    public function listado($f3)
    {
        $result = $this->m_categoria->find();
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
