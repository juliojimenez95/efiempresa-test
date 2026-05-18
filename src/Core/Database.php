<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Clase de conexión a la Base de Datos utilizando el Patrón Singleton.
 * Garantiza una única conexión PDO activa por petición.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    /**
     * Constructor privado para prevenir instanciación externa.
     */
    private function __construct()
    {
        // Ruta absoluta al archivo de configuración desde src/Core/Database.php
        $configFile = dirname(__DIR__, 2) . '/config/database.php';

        if (!file_exists($configFile)) {
            throw new \RuntimeException(
                sprintf("Error de Infraestructura: Archivo de configuración de base de datos no encontrado en '%s'.", $configFile)
            );
        }

        /** @var array $config */
        $config = require $configFile;
        $dbConfig = $config['db'] ?? [];

        if (empty($dbConfig)) {
            throw new \RuntimeException(
                "Error de Infraestructura: La sección de configuración 'db' está vacía o mal estructurada."
            );
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $dbConfig['host'] ?? '127.0.0.1',
            $dbConfig['port'] ?? 3306,
            $dbConfig['dbname'] ?? 'vasalto_competencias',
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        try {
            $this->connection = new PDO(
                $dsn,
                $dbConfig['username'] ?? 'root',
                $dbConfig['password'] ?? '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Desactivar emulación para prepared statements reales
                ]
            );
        } catch (PDOException $e) {
            // Log de error interno y ocultación de credenciales en excepciones al exterior
            error_log(sprintf("[DatabaseConnectionError] Código: %d - Mensaje: %s", (int) $e->getCode(), $e->getMessage()));
            throw new \RuntimeException(
                "Error Crítico: No se pudo establecer la conexión con el servidor de base de datos.",
                0,
                $e
            );
        }
    }

    /**
     * Obtiene la instancia única de Database.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retorna la conexión PDO activa.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prevenir clonación de la instancia única.
     */
    private function __clone() {}

    /**
     * Prevenir deserialización de la instancia única.
     */
    public function __wakeup(): void
    {
        throw new \RuntimeException("No está permitido deserializar un Singleton de base de datos.");
    }
}
