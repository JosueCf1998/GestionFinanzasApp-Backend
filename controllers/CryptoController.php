<?php

namespace Controllers;

require_once __DIR__ . '/BaseController.php';

class CryptoController extends BaseController
{
    protected $transferModel;

    public function __construct()
    {
        parent::__construct();
        $this->transferModel = new \m_transferencias();
    }

    public function encryption($f3)
    {
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $value = $body['value'] ?? $body['text'] ?? null;
        if (!$value) {
            // If no specific `value` provided, encrypt the whole body payload
            $value = json_encode($body);
            if ($value === false || $value === 'null' || $value === '') {
                $this->errorResponse('No se proporcionó valor a cifrar', 400);
                return;
            }
        }

        try {
            $encrypted = \SecurityHelper::encryptData($value, $this->encryptionKey, $this->iv);
            $this->successResponse([
                'original' => $value,
                'encrypted' => $encrypted
            ], 'Cifrado correcto');
        } catch (\Exception $e) {
            $this->errorResponse('Error al cifrar: ' . $e->getMessage(), 500);
        }
    }

    public function decryption($f3)
    {
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $encrypted = $body['value'] ?? $body['encrypted'] ?? null;
        if (!$encrypted) {
            $this->errorResponse('No se proporcionó valor a desencriptar', 400);
            return;
        }

        try {
            $decrypted = \SecurityHelper::decryptData($encrypted, $this->encryptionKey, $this->iv);
            // If decrypted payload is JSON, decode it for the response
            $decoded = json_decode($decrypted, true);
            $decryptedValue = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : $decrypted;
            $this->successResponse([
                'encrypted' => $encrypted,
                'decrypted' => $decryptedValue
            ], 'Descifrado correcto');
        } catch (\Exception $e) {
            $this->errorResponse('Error al desencriptar: ' . $e->getMessage(), 500);
        }
    }

}
