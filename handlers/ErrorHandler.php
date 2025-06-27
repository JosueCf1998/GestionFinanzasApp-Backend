<?php

require_once __DIR__.'/../config/error_codes.php';

class ErrorHandler
{
    private static $errorConfig;

    public static function initialize()
    {
        self::$errorConfig = require __DIR__.'/../config/error_codes.php';
    }

    /**
     * Maneja una excepción y devuelve una respuesta JSON apropiada
     */
    public static function handleException(Throwable $e): void
    {
        $errorInfo = self::getErrorInfo($e);
        
        header('Content-Type: application/json');
        http_response_code($errorInfo['http_code']);
        
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $errorInfo['code'],
                'type' => $errorInfo['type'],
                'message' => $errorInfo['message'],
                'timestamp' => date('c'),
                'details' => $errorInfo['details'] ?? null
            ]
        ]);
        exit;
    }

    /**
     * Obtiene información detallada del error
     */
    private static function getErrorInfo(Throwable $e): array
    {
        // Si es una AppException personalizada
        if ($e instanceof AppException) {
            return [
                'code' => $e->getCode(),
                'type' => $e->getErrorType(),
                'message' => $e->getMessage(),
                'http_code' => $e->getHttpCode(),
                'details' => $e->getDetails()
            ];
        }

        // Buscar en la configuración de errores
        foreach (self::$errorConfig as $category => $errors) {
            foreach ($errors as $error) {
                if ($error['code'] === $e->getCode()) {
                    return [
                        'code' => $error['code'],
                        'type' => $category,
                        'message' => $e->getMessage() ?: $error['message'],
                        'http_code' => $error['http_code'],
                        'details' => null
                    ];
                }
            }
        }

        // Error no mapeado
        return [
            'code' => 1300,
            'type' => 'SERVER',
            'message' => 'Error interno del servidor',
            'http_code' => 500,
            'details' => [
                'original_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ];
    }

    /**
     * Lanza una excepción con un código de error específico
     */
    public static function throwError(string $category, string $errorKey, string $customMessage = null, array $details = null): void
    {
        if (!isset(self::$errorConfig[$category][$errorKey])) {
            throw new RuntimeException("Error configuration not found for $category.$errorKey");
        }

        $errorConfig = self::$errorConfig[$category][$errorKey];
        
        throw new AppException(
            $customMessage ?? $errorConfig['message'],
            $errorConfig['code'],
            $category,
            $errorConfig['http_code'],
            $details
        );
    }

    /**
     * Registra el error en el sistema de logs
     */
    public static function logError(Throwable $e): void
    {
        $errorInfo = self::getErrorInfo($e);
        $logMessage = sprintf(
            "[%s] [%s:%d] %s: %s\n%s",
            date('Y-m-d H:i:s'),
            $errorInfo['type'],
            $errorInfo['code'],
            $errorInfo['message'],
            $e->getTraceAsString(),
            $errorInfo['details'] ? json_encode($errorInfo['details']) : ''
        );

        error_log($logMessage);
    }
}

// Inicializar el manejador de errores
ErrorHandler::initialize();

// Registrar el manejador de excepciones global
set_exception_handler([ErrorHandler::class, 'handleException']);