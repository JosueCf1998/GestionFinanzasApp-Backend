<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JwtHelper
{
    private static $algorithm = 'HS256';
    
    public static function generateToken(array $payload, string $secretKey, int $expiry = 100000000): string
    {
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + $expiry
        ]);
        return JWT::encode($payload, $secretKey, self::$algorithm);
    }
    
    public static function validateToken(string $token, string $secretKey): object
    {
        try {
            return JWT::decode($token, new Key($secretKey, self::$algorithm));
        } catch (Exception $e) {
            throw new RuntimeException('Token inválido: ' . $e->getMessage());
        }
    }
    
    public static function getBearerToken(): string
    {
        $headers = getallheaders();
        
        if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        
        throw new RuntimeException('Token no proporcionado', 401);
    }
}