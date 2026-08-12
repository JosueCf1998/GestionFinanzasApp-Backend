<?php
require_once __DIR__ . '/vendor/autoload.php';

$localConfig = [];
$localConfigPath = __DIR__ . '/config.local.ini';
if (is_file($localConfigPath)) {
    $parsedConfig = parse_ini_file($localConfigPath, true, INI_SCANNER_RAW);
    $localConfig = is_array($parsedConfig) ? $parsedConfig : [];
}

$configValue = static function (string $environmentName, string $section, string $key, ?string $default = null) use ($localConfig): ?string {
    $environmentValue = getenv($environmentName);
    if ($environmentValue !== false) {
        return $environmentValue;
    }
    return isset($localConfig[$section][$key]) ? (string)$localConfig[$section][$key] : $default;
};

$appEnvironment = $configValue('APP_ENV', 'application', 'environment', 'production');
$debugValue = $configValue('APP_DEBUG', 'application', 'debug', 'false');
$allowedOrigin = $configValue('APP_ALLOWED_ORIGIN', 'application', 'allowed_origin');

if ($allowedOrigin !== null && $allowedOrigin !== '') {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Vary: Origin');
}
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Cargar el core del framework
$f3 = require('base.php'); // o usa 'vendor/autoload.php' si lo tienes con Composer

$f3->set('DEBUG', filter_var($debugValue, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
$f3->set('APP_ENV', $appEnvironment);

// Verificar versión de PCRE
if ((float) PCRE_VERSION < 8.0) {
    trigger_error('PCRE version is out of date', E_USER_WARNING);
}

// Cargar configuración desde archivo INI
$f3->config('config.ini');
$f3->config('routes.ini');

$dbConfig = [
    'host' => $configValue('DB_HOST', 'database', 'host'),
    'port' => $configValue('DB_PORT', 'database', 'port', '3306'),
    'name' => $configValue('DB_NAME', 'database', 'dbname'),
    'user' => $configValue('DB_USER', 'database', 'user'),
    'password' => $configValue('DB_PASSWORD', 'database', 'pass', '')
];

$secretConfig = [
    'JWT_SECRET' => $configValue('JWT_SECRET', 'security', 'jwt_secret'),
    'ENCRYPTION_KEY' => $configValue('ENCRYPTION_KEY', 'security', 'encryption_key'),
    'ENCRYPTION_JSON' => $configValue('ENCRYPTION_JSON', 'security', 'encryption_json')
];

foreach (['host', 'name', 'user'] as $requiredSetting) {
    if ($dbConfig[$requiredSetting] === null || $dbConfig[$requiredSetting] === '') {
        throw new RuntimeException('Database configuration is incomplete');
    }
}
foreach ($secretConfig as $environmentName => $secretValue) {
    if ($secretValue === null || $secretValue === '') {
        throw new RuntimeException('Security configuration is incomplete');
    }
    if (getenv($environmentName) === false) {
        putenv($environmentName . '=' . $secretValue);
    }
}

$options = array(
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_PERSISTENT => false,
    \PDO::ATTR_EMULATE_PREPARES => false
);

try {
    $db = new \DB\SQL(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $dbConfig['host'], (int)$dbConfig['port'], $dbConfig['name']),
        $dbConfig['user'],
        $dbConfig['password'],
        $options
    );
} catch (Throwable $e) {
    error_log('Database connection failed');
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No fue posible conectar con la base de datos.',
        'error' => ['code' => 'DATABASE_CONNECTION_ERROR']
    ]);
    exit;
}
$f3->set('DB', $db);



$f3->run();
