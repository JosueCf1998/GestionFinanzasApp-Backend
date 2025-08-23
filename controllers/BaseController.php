<?php

class BaseController
{
    
    protected function successResponse($data = [], string $message = 'Operación exitosa'): void
    {
        ResponseHelper::success($data, $message);
    }
    
    protected function errorResponse(string $message = 'Error en la operación', int $code = 500, array $errors = []): void
    {
        ResponseHelper::error($message, $code, $errors);
    }
    
    protected function validationError(array $errors = [], string $message = 'Error de validación'): void
    {
        ResponseHelper::validationError($errors, $message);
    }
    
    protected function validateRequestMethod(string $expectedMethod): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== $expectedMethod) {
            $this->errorResponse('Método no permitido', 405);
        }
    }
    
    protected function parseAndValidateRequest(string $requestBody): array
    {
        $data = json_decode($requestBody, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errorResponse('Formato JSON inválido', 400);
        }
        
        return SecurityHelper::sanitizeInput($data);
    }
    
    protected function handleError(Exception $e): void
    {
        error_log('Error: ' . $e->getMessage());
        $this->errorResponse($e->getMessage(), (int)($e->getCode() ?: 500));
    }
    
    
    
}