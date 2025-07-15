<?php

class SessionHelper
{
    public static function createSession(\Base $f3, int $userId, string $token): void
    {
        $db = $f3->get('DB');
        $now = date('Y-m-d H:i:s');
        
        $db->exec(
            "INSERT INTO sesiones (user_id, token, ultimo_uso, creado_en) VALUES (?, ?, ?, ?)",
            [$userId, $token, $now, $now]
        );
    }
    
    public static function verifySession(\Base $f3, int $userId, string $token): void
    {
        $db = $f3->get('DB');
        $session = $db->exec("SELECT * FROM sesiones WHERE user_id = ? AND token = ?", [$userId, $token]);
        
        if (empty($session)) {
            throw new RuntimeException('Sesión no encontrada', 401);
        }
        
        $lastUsed = strtotime($session[0]['ultimo_uso']);
        $now = time();
        
        if (($now - $lastUsed) > (60 * 5)) { // 5 minutos de inactividad
            $db->exec("DELETE FROM sesiones WHERE user_id = ? AND token = ?", [$userId, $token]);
            throw new RuntimeException('Sesión expirada por inactividad', 401);
        }
        
        // Actualizar último uso
        $db->exec("UPDATE sesiones SET ultimo_uso = ? WHERE user_id = ? AND token = ?", 
                 [date('Y-m-d H:i:s'), $userId, $token]);
    }
    
    public static function destroySession(\Base $f3, int $userId, string $token): void
    {
        $db = $f3->get('DB');
        $db->exec("DELETE FROM sesiones WHERE user_id = ? AND token = ?", [$userId, $token]);
    }

    public static function storeSession($f3, $userId, $token, $dateCreated = null)
{
    // Ejemplo: guardar la sesión en la base de datos
    $db = $f3->get('DB');
    $db->exec(
        "INSERT INTO sesiones (user_id, token, creado_en) VALUES (?, ?, NOW())",
        [$userId, $token]
    );
}
}

