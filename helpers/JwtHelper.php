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
        } catch (\Firebase\JWT\ExpiredException $e) {
            throw new RuntimeException('Token expirado. Por favor, inicie sesión nuevamente.', 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            throw new RuntimeException('Token inválido: La firma no coincide.', 401);
        } catch (\Firebase\JWT\BeforeValidException $e) {
            throw new RuntimeException('Token aún no válido.', 401);
        } catch (Exception $e) {
            throw new RuntimeException('Token inválido: ' . $e->getMessage(), 401);
        }
    }
    
    public static function getBearerToken(): string
    {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            throw new RuntimeException('Header de autorización no proporcionado.', 401);
        }
        
        $authHeader = trim($headers['Authorization']);
        
        // Extraer el token: puede venir como "Bearer token" o solo "token"
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        
        return $authHeader;
    }
}