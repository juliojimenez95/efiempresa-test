<?php

declare(strict_types=1);

/**
 * Configuración de la base de datos para el Gestor de Competencias.
 * Retorna las credenciales y parámetros de conexión para el contenedor Docker de MySQL 8.0.
 */
return [
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int) (getenv('DB_PORT') ?: 3306),
        'dbname'   => getenv('DB_NAME') ?: 'vasalto_competencias',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root_secure_pass',
        'charset'  => 'utf8mb4'
    ]
];

