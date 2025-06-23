<?php

class SecurityHelper
{
    private static $cipher = 'AES-256-CBC';
    
    public static function encryptData(string $data, string $key, string $iv): string
    {
        $encrypted = openssl_encrypt($data, self::$cipher, $key, 0, $iv);
        if ($encrypted === false) {
            throw new RuntimeException('Error al encriptar datos: ' . openssl_error_string());
        }
        return $encrypted;
    }
    
    public static function decryptData(string $data, string $key, string $iv): string
    {
        $decrypted = openssl_decrypt($data, self::$cipher, $key, 0, $iv);
        if ($decrypted === false) {
            throw new RuntimeException('Error al desencriptar datos: ' . openssl_error_string());
        }
        return $decrypted;
    }
    
    public static function generateIV(string $key): string
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