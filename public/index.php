<?php

declare(strict_types=1);

/**
 * Front Controller — Punto de Entrada Único del Aplicativo.
 * Inicializa el buffer de salida, registra el autoloader y despacha las peticiones HTTP.
 */

// 1. Inicializar el almacenamiento en buffer para el control de cabeceras HTTP
ob_start();

// 2. Registro del Autoloader PSR-4 para cargar clases de forma dinámica
$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    // Autoloader fallback PSR-4 nativo para portabilidad sin Composer
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/src/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    });
}

// 3. Inicializar el Service Container para la Inyección de Dependencias
use App\Core\Container;
use App\Core\Router;

$container = new Container();

// Registrar la instancia única del Router y la conexión de BD en el contenedor
$container->singleton(Router::class, function () {
    return new Router();
});

// 4. Configurar las rutas del aplicativo
/** @var Router $router */
$router = $container->get(Router::class);

// --- Rutas de Presentación (Vistas HTML) ---

// Catálogo / Configuración inicial (Ruta por defecto)
$router->get('/', function () {
    $viewPath = __DIR__ . '/views/catalogo.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo '<h1>Gestor de Competencias</h1><p>El catálogo se encuentra pendiente de maquetación, parce.</p>';
    }
});

// Catálogo / Configuración
$router->get('/catalogo', function () {
    $viewPath = __DIR__ . '/views/catalogo.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo '<h1>Configuración del Catálogo</h1><p>Catálogo en construcción, parce.</p>';
    }
});

// Pantalla One-to-One para evaluar
$router->get('/evaluacion', function () {
    $viewPath = __DIR__ . '/views/evaluacion.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo '<h1>Evaluación One-to-One</h1><p>Vista de evaluación en construcción, parce.</p>';
    }
});

// Ficha individual con histórico
$router->get('/ficha', function () {
    $viewPath = __DIR__ . '/views/ficha.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo '<h1>Ficha del Empleado</h1><p>Ficha de competencias en construcción, parce.</p>';
    }
});

// Matriz resumen por equipo
$router->get('/matriz', function () {
    $viewPath = __DIR__ . '/views/matriz.php';
    if (file_exists($viewPath)) {
        require_once $viewPath;
    } else {
        echo '<h1>Matriz de Competencias</h1><p>Matriz de equipo en construcción, parce.</p>';
    }
});

// --- Rutas de API / AJAX (Controllers del módulo) ---
// Mapeamos temporalmente con Closures de prueba para validar que el Router y Container responden JSON perfectamente.
// En las siguientes actividades del Bloque 3, estas llamadas se redireccionarán a sus respectivos Controllers.

$router->post('/api/evaluacion', function () {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'message' => 'Simulación de guardado con Fetch API exitosa, parce.',
        'data' => [
            'porcentaje_ajuste' => 87.5,
            'evaluaciones' => []
        ]
    ], JSON_UNESCAPED_UNICODE);
});

$router->get('/api/test-db', function () {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdo = \App\Core\Database::getInstance()->getConnection();
        $stmt = $pdo->query('SELECT VERSION() AS version');
        $version = $stmt->fetchColumn();
        echo json_encode([
            'status' => 'success',
            'message' => 'Conexión a MySQL 8.0 exitosa desde el Singleton PDO, parce.',
            'db_version' => $version
        ], JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Fallo al conectar con la base de datos.',
            'details' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
});

// 5. Despachar la petición resolviendo las dependencias automáticamente
$router->dispatch($container);

// Enviar el contenido del buffer y cerrarlo
ob_end_flush();
