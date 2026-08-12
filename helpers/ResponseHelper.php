<?php

class ResponseHelper
{
    public static function success($data = [], string $message = 'Operación exitosa', int $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }

    public static function error(string $message = 'Error en la operación', int $code = 500, array $errors = [])
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('c'),
            'code' => $code
        ]);
        exit;
    }

    public static function codedError(string $message, int $httpCode, string $errorCode): void
    {
        header('Content-Type: application/json');
        http_response_code($httpCode);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'error' => ['code' => $errorCode],
            'timestamp' => date('c')
        ]);
        exit;
    }

    public static function validationError(array $errors = [], string $message = 'Error de validación')
    {
        self::error($message, 422, $errors);
    }
}
