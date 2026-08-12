<?php

class EmailCodeService
{
    public const EMAIL_VERIFICATION = 'EMAIL_VERIFICATION';
    public const PASSWORD_RESET = 'PASSWORD_RESET';
    public const EXPIRES_IN = 600;
    public const RESEND_AFTER = 60;
    public const MAX_ATTEMPTS = 5;

    private $db;

    public function __construct($db) { $this->db = $db; }

    public function create(int $userId, string $purpose, bool $enforceCooldown = false): string
    {
        $this->assertPurpose($purpose);
        $latest = $this->db->exec('SELECT created_at FROM email_codes WHERE user_id = ? AND purpose = ? ORDER BY id DESC LIMIT 1', [$userId, $purpose]);
        if ($enforceCooldown && $latest && time() - strtotime($latest[0]['created_at']) < self::RESEND_AFTER) {
            throw new AuthFlowException('Espera antes de solicitar otro código.', 'VERIFICATION_RESEND_TOO_SOON', 429);
        }
        $now = date('Y-m-d H:i:s');
        $this->db->exec(
            'UPDATE email_codes SET used_at = COALESCE(used_at, ?), reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ? AND purpose = ?',
            [$now, $userId, $purpose]
        );
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->db->exec('INSERT INTO email_codes (user_id, purpose, code_hash, expires_at) VALUES (?, ?, ?, ?)', [
            $userId, $purpose, password_hash($code, PASSWORD_DEFAULT), date('Y-m-d H:i:s', time() + self::EXPIRES_IN)
        ]);
        return $code;
    }

    public function verify(int $userId, string $purpose, string $code, bool $consume = true): array
    {
        $this->assertPurpose($purpose);
        if (!preg_match('/^\d{6}$/', $code)) $this->fail('Código inválido.', 'VERIFICATION_CODE_INVALID');
        $rows = $this->db->exec('SELECT * FROM email_codes WHERE user_id = ? AND purpose = ? ORDER BY id DESC LIMIT 1 FOR UPDATE', [$userId, $purpose]);
        if (!$rows) $this->fail('Código inválido.', 'VERIFICATION_CODE_INVALID');
        $row = $rows[0];
        if ($row['used_at'] !== null) $this->fail('El código ya fue utilizado.', 'VERIFICATION_CODE_USED', 409);
        if (strtotime($row['expires_at']) <= time()) $this->fail('El código ha vencido.', 'VERIFICATION_CODE_EXPIRED', 410);
        if ((int)$row['attempts'] >= self::MAX_ATTEMPTS) $this->fail('Se excedió el máximo de intentos.', 'VERIFICATION_ATTEMPTS_EXCEEDED', 429);
        if (!password_verify($code, $row['code_hash'])) {
            $attempts = (int)$row['attempts'] + 1;
            $this->db->exec('UPDATE email_codes SET attempts = ? WHERE id = ?', [$attempts, $row['id']]);
            if ($attempts >= self::MAX_ATTEMPTS) $this->fail('Se excedió el máximo de intentos.', 'VERIFICATION_ATTEMPTS_EXCEEDED', 429);
            $this->fail('Código inválido.', 'VERIFICATION_CODE_INVALID');
        }
        if ($consume) $this->db->exec('UPDATE email_codes SET used_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $row['id']]);
        return $row;
    }

    public function attachResetToken(int $codeId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->exec('UPDATE email_codes SET used_at = ?, reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?', [
            date('Y-m-d H:i:s'), hash('sha256', $token), date('Y-m-d H:i:s', time() + self::EXPIRES_IN), $codeId
        ]);
        return $token;
    }

    public function findResetToken(string $token): array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) $this->fail('Token inválido.', 'PASSWORD_RESET_TOKEN_INVALID');
        $rows = $this->db->exec('SELECT * FROM email_codes WHERE reset_token_hash = ? LIMIT 1 FOR UPDATE', [hash('sha256', $token)]);
        if (!$rows || $rows[0]['reset_token_hash'] === null) $this->fail('Token inválido.', 'PASSWORD_RESET_TOKEN_INVALID');
        if (strtotime($rows[0]['reset_token_expires_at']) <= time()) $this->fail('El token ha vencido.', 'PASSWORD_RESET_TOKEN_EXPIRED', 410);
        return $rows[0];
    }

    public function invalidateResetToken(int $id): void
    {
        $this->db->exec('UPDATE email_codes SET reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = ?', [$id]);
    }

    private function assertPurpose(string $purpose): void
    {
        if (!in_array($purpose, [self::EMAIL_VERIFICATION, self::PASSWORD_RESET], true)) throw new InvalidArgumentException('Propósito inválido');
    }

    private function fail(string $message, string $code, int $http = 400): void { throw new AuthFlowException($message, $code, $http); }
}
