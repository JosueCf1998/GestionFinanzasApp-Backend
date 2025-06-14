<?php

class BaseController
{
    protected function successResponse($data = [], $mensaje = 'Operación exitosa', $code = 200)
    {
        http_response_code($code);
        echo json_encode([
            'statusCode' => $code,
            'success' => true,
            'message' => $mensaje,
            'data' => $data
        ]);
        exit;
    }

    protected function errorResponse($mensaje = 'Error en la operación', $code = 500, $error = [])
    {
        http_response_code($code);
        echo json_encode([
            'statusCode' => $code,
            'success' => false,
            'message' => $mensaje,
            'error' => [
                'code' => isset($error['code']) ? $error['code'] : 'SERVER_ERROR',
                'description' => isset($error['description']) ? $error['description'] : $mensaje,
                'details' => $error
            ]
        ]);
        exit;
    }
}