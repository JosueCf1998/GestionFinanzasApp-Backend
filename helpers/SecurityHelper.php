<?php

class SecurityHelper
{
    private static $cipher = 'AES-256-CBC';
    
    public static function encryptData($data, $key, $iv)
        {
            return base64_encode(
                openssl_encrypt(
                    $data,
                    'AES-256-CBC',
                    $key,
                    OPENSSL_RAW_DATA,
                    $iv
                )
            );
        }

    
    public static function decryptData($data, $key, $iv)
        {
            return openssl_decrypt(
                base64_decode($data),
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
        }

    public static function generateIV($key)
    {
        return substr(hash('sha256', $key), 0, 16);
    }

    
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}