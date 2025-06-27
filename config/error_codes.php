<?php

return [
    /*
     * Errores de Autenticación y Autorización (1000-1099)
     */
    'AUTHENTICATION' => [
        'INVALID_CREDENTIALS' => [
            'code' => 1000,
            'message' => 'Credenciales inválidas',
            'http_code' => 401
        ],
        'MISSING_TOKEN' => [
            'code' => 1001,
            'message' => 'Token de acceso no proporcionado',
            'http_code' => 401
        ],
        'INVALID_TOKEN' => [
            'code' => 1002,
            'message' => 'Token de acceso inválido o malformado',
            'http_code' => 403
        ],
        'EXPIRED_TOKEN' => [
            'code' => 1003,
            'message' => 'Token de acceso expirado',
            'http_code' => 403
        ],
        'INSUFFICIENT_PERMISSIONS' => [
            'code' => 1004,
            'message' => 'Permisos insuficientes para esta acción',
            'http_code' => 403
        ],
        'ACCOUNT_DISABLED' => [
            'code' => 1005,
            'message' => 'Cuenta deshabilitada',
            'http_code' => 403
        ],
        'SESSION_EXPIRED' => [
            'code' => 1006,
            'message' => 'Sesión expirada por inactividad',
            'http_code' => 401
        ],
        'INVALID_REFRESH_TOKEN' => [
            'code' => 1007,
            'message' => 'Token de refresco inválido',
            'http_code' => 403
        ],
        'MFA_REQUIRED' => [
            'code' => 1008,
            'message' => 'Autenticación multifactor requerida',
            'http_code' => 401
        ],
        'MFA_FAILED' => [
            'code' => 1009,
            'message' => 'Verificación multifactor fallida',
            'http_code' => 401
        ]
    ],

    /*
     * Errores de Validación (1100-1199)
     */
    'VALIDATION' => [
        'GENERAL' => [
            'code' => 1100,
            'message' => 'Error de validación',
            'http_code' => 400
        ],
        'REQUIRED_FIELD' => [
            'code' => 1101,
            'message' => 'Campo requerido faltante',
            'http_code' => 400
        ],
        'INVALID_EMAIL' => [
            'code' => 1102,
            'message' => 'El formato del email es inválido',
            'http_code' => 400
        ],
        'WEAK_PASSWORD' => [
            'code' => 1103,
            'message' => 'La contraseña debe tener al menos 8 caracteres',
            'http_code' => 400
        ],
        'PASSWORD_MISMATCH' => [
            'code' => 1104,
            'message' => 'Las contraseñas no coinciden',
            'http_code' => 400
        ],
        'DUPLICATE_ENTRY' => [
            'code' => 1105,
            'message' => 'El recurso ya existe',
            'http_code' => 409
        ],
        'INVALID_DATE_FORMAT' => [
            'code' => 1106,
            'message' => 'Formato de fecha inválido',
            'http_code' => 400
        ],
        'VALUE_TOO_LONG' => [
            'code' => 1107,
            'message' => 'El valor excede la longitud máxima permitida',
            'http_code' => 400
        ],
        'INVALID_CHOICE' => [
            'code' => 1108,
            'message' => 'Opción seleccionada inválida',
            'http_code' => 400
        ],
        'INVALID_FILE_TYPE' => [
            'code' => 1109,
            'message' => 'Tipo de archivo no permitido',
            'http_code' => 400
        ]
    ],

    /*
     * Errores de Recursos (1200-1299)
     */
    'RESOURCE' => [
        'USER_NOT_FOUND' => [
            'code' => 1200,
            'message' => 'Usuario no encontrado',
            'http_code' => 404
        ],
        'PROFILE_NOT_FOUND' => [
            'code' => 1201,
            'message' => 'Perfil no encontrado',
            'http_code' => 404
        ],
        'RESOURCE_NOT_FOUND' => [
            'code' => 1202,
            'message' => 'Recurso no encontrado',
            'http_code' => 404
        ],
        'RESOURCE_ALREADY_EXISTS' => [
            'code' => 1203,
            'message' => 'El recurso ya existe',
            'http_code' => 409
        ],
        'RESOURCE_LIMIT_REACHED' => [
            'code' => 1204,
            'message' => 'Límite de recursos alcanzado',
            'http_code' => 403
        ],
        'RESOURCE_CONFLICT' => [
            'code' => 1205,
            'message' => 'Conflicto con el recurso',
            'http_code' => 409
        ],
        'RESOURCE_READ_ONLY' => [
            'code' => 1206,
            'message' => 'El recurso es de solo lectura',
            'http_code' => 403
        ],
        'RESOURCE_DEPENDENCY' => [
            'code' => 1207,
            'message' => 'El recurso tiene dependencias',
            'http_code' => 409
        ]
    ],

    /*
     * Errores del Servidor (1300-1399)
     */
    'SERVER' => [
        'INTERNAL_ERROR' => [
            'code' => 1300,
            'message' => 'Error interno del servidor',
            'http_code' => 500
        ],
        'SERVICE_UNAVAILABLE' => [
            'code' => 1301,
            'message' => 'Servicio no disponible temporalmente',
            'http_code' => 503
        ],
        'MAINTENANCE_MODE' => [
            'code' => 1302,
            'message' => 'Sistema en mantenimiento',
            'http_code' => 503
        ],
        'REQUEST_TIMEOUT' => [
            'code' => 1303,
            'message' => 'Tiempo de espera agotado',
            'http_code' => 408
        ],
        'NOT_IMPLEMENTED' => [
            'code' => 1304,
            'message' => 'Funcionalidad no implementada',
            'http_code' => 501
        ],
        'BAD_GATEWAY' => [
            'code' => 1305,
            'message' => 'Error de comunicación con servicio externo',
            'http_code' => 502
        ],
        'STORAGE_ERROR' => [
            'code' => 1306,
            'message' => 'Error de almacenamiento',
            'http_code' => 500
        ],
        'ENCRYPTION_ERROR' => [
            'code' => 1307,
            'message' => 'Error en proceso de encriptación',
            'http_code' => 500
        ],
        'DECRYPTION_ERROR' => [
            'code' => 1308,
            'message' => 'Error en proceso de desencriptación',
            'http_code' => 500
        ]
    ],

    /*
     * Errores de Base de Datos (1400-1499)
     */
    'DATABASE' => [
        'CONNECTION_ERROR' => [
            'code' => 1400,
            'message' => 'Error de conexión a la base de datos',
            'http_code' => 500
        ],
        'QUERY_ERROR' => [
            'code' => 1401,
            'message' => 'Error en la consulta SQL',
            'http_code' => 500
        ],
        'DUPLICATE_KEY' => [
            'code' => 1402,
            'message' => 'Violación de clave única',
            'http_code' => 409
        ],
        'FOREIGN_KEY_CONSTRAINT' => [
            'code' => 1403,
            'message' => 'Violación de clave foránea',
            'http_code' => 409
        ],
        'TRANSACTION_ERROR' => [
            'code' => 1404,
            'message' => 'Error en transacción',
            'http_code' => 500
        ],
        'DEADLOCK' => [
            'code' => 1405,
            'message' => 'Deadlock detectado',
            'http_code' => 500
        ],
        'TIMEOUT' => [
            'code' => 1406,
            'message' => 'Timeout de base de datos',
            'http_code' => 504
        ],
        'SCHEMA_ERROR' => [
            'code' => 1407,
            'message' => 'Error de esquema',
            'http_code' => 500
        ]
    ],

    /*
     * Errores de API Externa (1500-1599)
     */
    'EXTERNAL_API' => [
        'CONNECTION_ERROR' => [
            'code' => 1500,
            'message' => 'Error de conexión con API externa',
            'http_code' => 502
        ],
        'INVALID_RESPONSE' => [
            'code' => 1501,
            'message' => 'Respuesta inválida de API externa',
            'http_code' => 502
        ],
        'RATE_LIMIT_EXCEEDED' => [
            'code' => 1502,
            'message' => 'Límite de tasa excedido en API externa',
            'http_code' => 429
        ],
        'AUTHENTICATION_FAILED' => [
            'code' => 1503,
            'message' => 'Autenticación fallida con API externa',
            'http_code' => 502
        ],
        'SERVICE_ERROR' => [
            'code' => 1504,
            'message' => 'Error en servicio externo',
            'http_code' => 502
        ],
        'TIMEOUT' => [
            'code' => 1505,
            'message' => 'Timeout en API externa',
            'http_code' => 504
        ]
    ],

    /*
     * Errores de Archivos y Subidas (1600-1699)
     */
    'FILE' => [
        'UPLOAD_ERROR' => [
            'code' => 1600,
            'message' => 'Error al subir archivo',
            'http_code' => 500
        ],
        'FILE_TOO_LARGE' => [
            'code' => 1601,
            'message' => 'El archivo excede el tamaño máximo permitido',
            'http_code' => 413
        ],
        'INVALID_FILE_TYPE' => [
            'code' => 1602,
            'message' => 'Tipo de archivo no permitido',
            'http_code' => 400
        ],
        'FILE_NOT_FOUND' => [
            'code' => 1603,
            'message' => 'Archivo no encontrado',
            'http_code' => 404
        ],
        'PERMISSION_DENIED' => [
            'code' => 1604,
            'message' => 'Permiso denegado para el archivo',
            'http_code' => 403
        ],
        'STORAGE_FULL' => [
            'code' => 1605,
            'message' => 'Almacenamiento lleno',
            'http_code' => 507
        ]
    ],

    /*
     * Errores de Formato de Datos (1700-1799)
     */
    'DATA' => [
        'INVALID_JSON' => [
            'code' => 1700,
            'message' => 'JSON inválido',
            'http_code' => 400
        ],
        'INVALID_XML' => [
            'code' => 1701,
            'message' => 'XML inválido',
            'http_code' => 400
        ],
        'MISSING_DATA' => [
            'code' => 1702,
            'message' => 'Datos faltantes',
            'http_code' => 400
        ],
        'INVALID_ENCODING' => [
            'code' => 1703,
            'message' => 'Codificación de caracteres inválida',
            'http_code' => 400
        ],
        'DATA_CORRUPTION' => [
            'code' => 1704,
            'message' => 'Corrupción de datos detectada',
            'http_code' => 500
        ],
        'INVALID_HASH' => [
            'code' => 1705,
            'message' => 'Hash de datos inválido',
            'http_code' => 400
        ]
    ]
];