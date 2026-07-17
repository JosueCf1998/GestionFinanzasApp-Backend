<?php

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

class JwtHelper
{
    private static $algorithm = 'HS256';

    private static function getAuthorizationHeader($f3 = null): ?string
    {
        $candidates = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $key => $value) {
                    if (strtolower((string)$key) === 'authorization' && !empty($value)) {
                        $candidates[] = $value;
                    }
                }
            }
        }

        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $candidates[] = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $candidates[] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if ($f3 !== null) {
            try {
                $f3Header = $f3->get('HEADERS.Authorization');
                if (!empty($f3Header)) {
                    $candidates[] = $f3Header;
                }
            } catch (\Throwable $e) {
                // Ignorar: F3 no disponible o sin la estructura esperada.
            }
        }

        foreach ($candidates as $candidate) {
            $value = trim((string)$candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
    
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
    
    public static function getBearerToken($f3 = null): string
    {
        $authHeader = self::getAuthorizationHeader($f3);

        if ($authHeader === null) {
            throw new RuntimeException('Header de autorización no proporcionado.', 401);
        }

        $authHeader = trim($authHeader);
        
        // Extraer el token: puede venir como "Bearer token" o solo "token"
        if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }
        
        return $authHeader;
    }
}