<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';

// Carga local de variables desde .env sin dependencias externas.
if (is_file(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2));
        if ($name === '') {
            continue;
        }

        $len = strlen($value);
        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === '\'' && $last === '\'')) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($name) === false) {
            putenv($name . '=' . $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Cargar el core del framework
$f3 = require('base.php'); // o usa 'vendor/autoload.php' si lo tienes con Composer

// Modo DEBUG (1 = errores visibles, 0 = silencioso)
$debug = getenv('APP_DEBUG');
$f3->set('DEBUG', $debug !== false ? (int)$debug : 1);

// Verificar versión de PCRE
if ((float) PCRE_VERSION < 8.0) {
    trigger_error('PCRE version is out of date', E_USER_WARNING);
}

// Cargar configuración desde archivo INI
$f3->config('config.ini');
$f3->config('routes.ini');

// Configuración de PDO (si vas a usar base de datos)
$options = array(
    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
    \PDO::ATTR_PERSISTENT => true,
    \PDO::MYSQL_ATTR_COMPRESS => true
);
// Crear la conexión a la base de datos
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'u992910078_finanzas_db';
$dbUser = getenv('DB_USER') ?: 'u992910078_sajogre2026';
$dbPass = getenv('DB_PASS') ?: '$#Greg1410#$';
$db = new \DB\SQL("mysql:host={$dbHost};port={$dbPort};dbname={$dbName}", $dbUser, $dbPass);
$f3->set('DB', $db);



$f3->run();
