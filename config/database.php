<?php

declare(strict_types=1);

/**
 * Configuración de la base de datos para el Gestor de Competencias.
 * Retorna las credenciales y parámetros de conexión para el contenedor Docker de MySQL 8.0.
 */
return [
    'db' => [
        'host'     => '127.0.0.1', // Usamos 127.0.0.1 por estabilidad en Windows/Docker
        'port'     => 3306,
        'dbname'   => 'vasalto_competencias',
        'username' => 'root',
        'password' => 'root_secure_pass',
        'charset'  => 'utf8mb4'
    ]
];
