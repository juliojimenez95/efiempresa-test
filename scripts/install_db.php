<?php

declare(strict_types=1);

/**
 * Script de inicialización y migración automática para el Gestor de Competencias.
 * Carga el esquema relacional y las semillas de prueba en MySQL 8.0.
 */

// Registrar el autoloader de clases del módulo
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

echo "=========================================================\n";
echo "🤖 EFIEMPRESA ERP - INSTALADOR DE BASE DE DATOS\n";
echo "=========================================================\n";

try {
    echo "🔄 Conectando con el Singleton PDO de Base de Datos...\n";
    $dbInstance = \App\Core\Database::getInstance();
    $pdo = $dbInstance->getConnection();
    echo "✅ Conexión establecida de forma exitosa.\n\n";

    $schemaPath = dirname(__DIR__) . '/database/schema.sql';
    $seedPath = dirname(__DIR__) . '/database/seed.sql';

    if (!file_exists($schemaPath) || !file_exists($seedPath)) {
        throw new RuntimeException("Error: No se encontraron los archivos schema.sql o seed.sql en /database.");
    }

    // 1. Ejecutar Schema
    echo "📝 Cargando el Esquema Relacional (schema.sql)...\n";
    $schemaSql = file_get_contents($schemaPath);
    $pdo->exec($schemaSql);
    echo "✅ Esquema de tablas e índices creado correctamente.\n\n";

    // 2. Ejecutar Semillas
    echo "🌱 Insertando Datos Maestros y Semillas de Prueba (seed.sql)...\n";
    $seedSql = file_get_contents($seedPath);
    $pdo->exec($seedSql);
    echo "✅ Semillas inicializadas correctamente.\n\n";

    echo "=========================================================\n";
    echo "🎉¡Socio, la Base de Datos ya está 100% instalada y lista!\n";
    echo "=========================================================\n";
    exit(0);

} catch (Throwable $e) {
    echo "❌ ERROR CRÍTICO durante la instalación:\n";
    echo $e->getMessage() . "\n";
    echo "Detalles del error: \n" . $e->getTraceAsString() . "\n";
    exit(1);
}
