<?php

declare(strict_types=1);

$controller = file_get_contents(__DIR__ . '/../controllers/UsersController.php');
$baseController = file_get_contents(__DIR__ . '/../controllers/BaseController.php');
$responseHelper = file_get_contents(__DIR__ . '/../helpers/ResponseHelper.php');

$tests = [
    'recuperacion directa permanece bloqueada' => str_contains($controller, 'PASSWORD_RESET_VERIFICATION_REQUIRED')
        && !str_contains($controller, 'newPasswordDecrypt'),
    'update autentica antes de procesar el body' => preg_match('/function update.*?validateToken\(\$f3\).*?parseAndValidateRequest/s', $controller) === 1,
    'update limita campos a nombre y apellidos' => str_contains($controller, "\$allowedFields = ['nombre', 'apellidos']"),
    'update no modifica email o password' => preg_match('/function updateUserData.*?set\(\x27(?:email|password)\x27/s', $controller) !== 1,
    'delete autentica y compara identidad' => preg_match('/function delete.*?validateToken\(\$f3\).*?requestedUserId/s', $controller) === 1,
    'delete protege datos financieros y usa transaccion' => str_contains($controller, 'ACCOUNT_DELETION_CONFLICT')
        && str_contains($controller, "\$db->begin()") && str_contains($controller, "\$db->rollback()"),
    'listado autentica y queda prohibido sin roles' => preg_match('/function listAll.*?validateToken\(\$f3\).*?FORBIDDEN/s', $controller) === 1,
    'respuestas de usuario eliminan password y usan allowlist' => str_contains($controller, "unset(\$userData['password'])")
        && str_contains($controller, "['id', 'nombre', 'apellidos', 'email']"),
    'sesion persistida forma parte de autenticacion' => str_contains($controller, 'SessionHelper::verifySession'),
    'errores estables usan error.code' => str_contains($responseHelper, "'error' => ['code' => \$errorCode]"),
    'errores internos no exponen mensajes' => str_contains($baseController, "errorResponse('Error interno del servidor', 500)"),
    'registro no transforma la contrasena' => str_contains($controller, "\$passwordPlain = \$body['password']")
];

$failed = 0;
foreach ($tests as $name => $passed) {
    echo ($passed ? '[OK] ' : '[FAIL] ') . $name . PHP_EOL;
    $failed += $passed ? 0 : 1;
}

exit($failed === 0 ? 0 : 1);
