<?php

use PragmaRX\Google2FA\Google2FA;

class OtpHelper
{
    private static function getGoogle2fa(): Google2FA
    {
        if (!class_exists(Google2FA::class)) {
            throw new \RuntimeException('Dependencia OTP no instalada. Ejecuta composer install');
        }

        return new Google2FA();
    }

    public static function generateSecret(): string
    {
        return self::getGoogle2fa()->generateSecretKey(32);
    }

    public static function getOtpAuthUrl(string $issuer, string $accountName, string $secret): string
    {
        return self::getGoogle2fa()->getQRCodeUrl($issuer, $accountName, $secret);
    }

    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        return self::getGoogle2fa()->verifyKey($secret, $code, $window);
    }
}
