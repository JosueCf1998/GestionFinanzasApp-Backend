<?php
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Cargar el core del framework
$f3 = require('base.php'); // o usa 'vendor/autoload.php' si lo tienes con Composer

// Modo DEBUG (1 = errores visibles, 0 = silencioso)
$f3->set('DEBUG', 1);

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
$db = new \DB\SQL('mysql:host=localhost;port=3306;dbname=u992910078_finanzas_db', 'root', 'root'); // cambia "tu_basedatos"
$f3->set('DB', $db);



$f3->run();
