<?php

namespace Utils;

class ResponseHelper
{ 
    public static function success($data = [], $mensaje = 'Operación exitosa', $code = 200)
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

    public static function error($mensaje = 'Error en la operación', $code = 500, $error = [])
    {
        http_response_code($code);
        echo json_encode([
            'statusCode' => $code,
            'success' => false,
            'message' => $mensaje,
            'error' => [
                'code' => $error['code'] ?? 'SERVER_ERROR',
                'description' => $error['description'] ?? $mensaje,
                'details' => $error
            ]
        ]);
        exit;
    }
}