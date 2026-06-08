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

    /**
     * Heurística simple para detectar si una cadena probablemente contiene
     * el formato esperado: salt(base64 24) + iv(base64 24) + ciphertext(base64...)
     */
    public static function looksEncrypted(string $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // Debe tener al menos salt+iv en base64
        $minLen = self::SALT_BASE64_LENGTH + self::IV_BASE64_LENGTH;
        if (strlen($value) < $minLen) {
            return false;
        }

        // Extraer y decodificar estrictamente (segundo parámetro true)
        $saltB64 = substr($value, 0, self::SALT_BASE64_LENGTH);
        $ivB64 = substr($value, self::SALT_BASE64_LENGTH, self::IV_BASE64_LENGTH);
        $cipherB64 = substr($value, $minLen);

        $salt = base64_decode($saltB64, true);
        $iv = base64_decode($ivB64, true);
        $cipher = base64_decode($cipherB64, true);

        if ($salt === false || $iv === false || $cipher === false) {
            return false;
        }

        // Salt e IV deben ser de 16 bytes (128 bits)
        if (strlen($salt) !== 16 || strlen($iv) !== 16) {
            return false;
        }

        // Ciphertext no debe estar vacío
        if (strlen($cipher) === 0) {
            return false;
        }

        return true;
    }
}