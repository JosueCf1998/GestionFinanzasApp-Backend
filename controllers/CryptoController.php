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
        $this->requireAuth($f3);
        $body = $this->parseJsonOrEncryptedBody($f3);
        if (empty($body)) {
            return;
        }

        $value = $body['value'] ?? $body['text'] ?? null;
        if (!$value) {
            $this->errorResponse('No se proporcionó valor a cifrar', 400);
            return;
        }

        try {
            $encrypted = \SecurityHelper::encryptData($value, $this->encryptionKey, $this->iv);
            $this->successResponse([
                'original' => $value,
                'encrypted' => $encrypted
            ], 'Encrypt successful');
        } catch (\Exception $e) {
            $this->errorResponse('Error al cifrar: ' . $e->getMessage(), 500);
        }
    }

    public function decryption($f3)
    {
        $this->requireAuth($f3);
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
            $this->successResponse([
                'encrypted' => $encrypted,
                'decrypted' => $decrypted
            ], 'Decrypt successful');
        } catch (\Exception $e) {
            $this->errorResponse('Error al desencriptar: ' . $e->getMessage(), 500);
        }
    }

}
