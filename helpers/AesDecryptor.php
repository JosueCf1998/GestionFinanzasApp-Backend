<?php

class AesDecryptor {
    const KEY_SIZE = 32; // 256 bits
    const ITERATIONS = 10000;
    const SALT_BASE64_LENGTH = 24; // 128 bits en Base64
    const IV_BASE64_LENGTH = 24;   // 128 bits en Base64

    public static function decrypt(string $encryptedData, string $password): string {
        try {
            // Validar longitud mínima
            if (strlen($encryptedData) < (self::SALT_BASE64_LENGTH + self::IV_BASE64_LENGTH)) {
                throw new Exception("Datos encriptados incompletos");
            }

            // Extraer componentes
            $salt = base64_decode(substr($encryptedData, 0, self::SALT_BASE64_LENGTH));
            $iv = base64_decode(substr($encryptedData, self::SALT_BASE64_LENGTH, self::IV_BASE64_LENGTH));
            $ciphertext = base64_decode(substr($encryptedData, self::SALT_BASE64_LENGTH + self::IV_BASE64_LENGTH));

            // Validar decodificación
            if ($salt === false || $iv === false || $ciphertext === false) {
                throw new Exception("Error al decodificar Base64");
            }

            // Derivar clave
            $key = openssl_pbkdf2(
                $password,
                $salt,
                self::KEY_SIZE,
                self::ITERATIONS,
                'sha256'
            );

            if ($key === false) {
                throw new Exception("Error al derivar clave");
            }

            // Desencriptar
            $decrypted = openssl_decrypt(
                $ciphertext,
                'aes-256-cbc',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new Exception("Error al desencriptar: " . openssl_error_string());
            }

            return $decrypted;
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            throw $e;
        }
    }
}