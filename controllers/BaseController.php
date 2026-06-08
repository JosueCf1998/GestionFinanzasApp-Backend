<?php

namespace Controllers;

class BaseController
{

    protected $jwtKey;
    protected $encryptionKey;
    protected $iv;

    public function __construct()
    {
        require_once __DIR__ . '/../helpers/ResponseHelper.php';
        require_once __DIR__ . '/../helpers/SecurityHelper.php';
        require_once __DIR__ . '/../helpers/JwtHelper.php';
        require_once __DIR__ . '/../helpers/SessionHelper.php';
        require_once __DIR__ . '/../helpers/AesDecryptor.php';

        $this->jwtKey = getenv('JWT_SECRET') ?: '$#Gre1410#$';
        $this->encryptionKey = getenv('ENCRYPTION_KEY') ?: '$#Gre1410';
        $this->iv = \SecurityHelper::generateIV($this->encryptionKey);
    }

    protected function successResponse($data = [], string $message = 'Operación exitosa'): void
    {
        \ResponseHelper::success($data, $message);
    }

    protected function errorResponse(string $message = 'Error en la operación', int $code = 500, array $errors = []): void
    {
        \ResponseHelper::error($message, $code, $errors);
    }

    protected function validationError(array $errors = [], string $message = 'Error de validación'): void
    {
        \ResponseHelper::validationError($errors, $message);
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

        return \SecurityHelper::sanitizeInput($data);
    }

    protected function requireAuth($f3)
    {
        $token = \JwtHelper::getBearerToken($f3);
        return \JwtHelper::validateToken($token, $this->jwtKey);
    }

    /**
     * Parse body supporting either raw JSON or an AES-encrypted payload { "data": "..." }
     * Returns parsed array or empty array on error (and sends error response).
     */
    protected function parseJsonOrEncryptedBody($f3): array
    {
        $bodyEncrypted = json_decode($f3->get('BODY'), true);
        if (is_array($bodyEncrypted) && isset($bodyEncrypted['data'])) {
            $dataField = $bodyEncrypted['data'];

            if (!\AesDecryptor::looksEncrypted($dataField)) {
                $this->errorResponse('El campo "data" no parece estar en formato encriptado válido', 400);
                return [];
            }

            try {
                $bodyDecrypted = \AesDecryptor::decrypt(
                    $dataField,
                    getenv('ENCRYPTION_JSON') ?: "TuClaveSuperSecreta@2024"
                );
            } catch (\Exception $e) {
                $this->errorResponse('Error al desencriptar payload JSON: ' . $e->getMessage(), 400);
                return [];
            }

            $body = json_decode($bodyDecrypted, true);
        } else {
            $body = $bodyEncrypted;
        }

        if (!$body || !is_array($body)) {
            $this->errorResponse('Error al procesar los datos', 400);
            return [];
        }

        return $body;
    }

    protected function handleError(\Exception $e): void
    {
        error_log('Error: ' . $e->getMessage());
        $this->errorResponse($e->getMessage(), (int)($e->getCode() ?: 500));
    }
    
}
