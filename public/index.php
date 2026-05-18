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

// Registrar la conexión PDO directamente para resolver dependencias de Repositories
$container->singleton(\PDO::class, function () {
    return \App\Core\Database::getInstance()->getConnection();
});

// Registrar bindings de las interfaces a sus implementaciones concretas
$container->singleton(\App\Repositories\Contracts\CompetenciaRepositoryInterface::class, function ($c) {
    return new \App\Repositories\PDOCompetenciaRepository($c->get(\PDO::class));
});

$container->singleton(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class, function ($c) {
    return new \App\Repositories\PDOEmpleadoRepository($c->get(\PDO::class));
});

$container->singleton(\App\Repositories\Contracts\PeriodoRepositoryInterface::class, function ($c) {
    return new \App\Repositories\PDOPeriodoRepository($c->get(\PDO::class));
});

$container->singleton(\App\Repositories\Contracts\PerfilObjetivoRepositoryInterface::class, function ($c) {
    return new \App\Repositories\PDOPerfilObjetivoRepository($c->get(\PDO::class));
});

$container->singleton(\App\Repositories\Contracts\EvaluacionRepositoryInterface::class, function ($c) {
    return new \App\Repositories\PDOEvaluacionRepository($c->get(\PDO::class));
});

// Registrar la calculadora matemática y el servicio orquestador de Evaluaciones
$container->singleton(\App\Services\CompetenciaCalculadoraServicio::class, function ($c) {
    return new \App\Services\CompetenciaCalculadoraServicio();
});

$container->singleton(\App\Services\EvaluacionService::class, function ($c) {
    return new \App\Services\EvaluacionService(
        $c->get(\App\Repositories\Contracts\EvaluacionRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\PerfilObjetivoRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\PeriodoRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class),
        $c->get(\App\Services\CompetenciaCalculadoraServicio::class)
    );
});

// Registrar los Controladores del módulo en el Contenedor (autowiring)
$container->singleton(\App\Controllers\EvaluacionController::class, function ($c) {
    return new \App\Controllers\EvaluacionController(
        $c->get(\App\Services\EvaluacionService::class),
        $c->get(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\PeriodoRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\PerfilObjetivoRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\EvaluacionRepositoryInterface::class)
    );
});

$container->singleton(\App\Controllers\MatrizController::class, function ($c) {
    return new \App\Controllers\MatrizController(
        $c->get(\App\Services\EvaluacionService::class),
        $c->get(\App\Repositories\Contracts\PeriodoRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class)
    );
});

$container->singleton(\App\Controllers\FichaController::class, function ($c) {
    return new \App\Controllers\FichaController(
        $c->get(\App\Services\EvaluacionService::class),
        $c->get(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class)
    );
});

$container->singleton(\App\Controllers\CatalogoController::class, function ($c) {
    return new \App\Controllers\CatalogoController(
        $c->get(\App\Repositories\Contracts\CompetenciaRepositoryInterface::class),
        $c->get(\App\Repositories\Contracts\PeriodoRepositoryInterface::class)
    );
});

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

// 1. Endpoints de Evaluación One-to-One
$router->get('/api/evaluacion', [\App\Controllers\EvaluacionController::class, 'obtenerFormulario']);
$router->get('/api/evaluacion/formulario', [\App\Controllers\EvaluacionController::class, 'obtenerFormulario']);
$router->post('/api/evaluacion', [\App\Controllers\EvaluacionController::class, 'store']);

// 2. Endpoints de la Matriz Resumen de Equipo
$router->get('/api/matriz', [\App\Controllers\MatrizController::class, 'obtenerMatriz']);
$router->get('/api/matriz/filtros', [\App\Controllers\MatrizController::class, 'obtenerFiltros']);

// 3. Endpoints de Ficha de Seguimiento Individual
$router->get('/api/ficha', [\App\Controllers\FichaController::class, 'obtenerFicha']);
$router->get('/api/empleados', [\App\Controllers\FichaController::class, 'obtenerEmpleados']);

// 4. Endpoints de CRUD Parametrizable (Catálogos y Competencias)
$router->get('/api/catalogo/competencias', [\App\Controllers\CatalogoController::class, 'obtenerCompetencias']);
$router->post('/api/catalogo/competencia', [\App\Controllers\CatalogoController::class, 'guardarCompetencia']);
$router->post('/api/catalogo/competencia/eliminar', [\App\Controllers\CatalogoController::class, 'eliminarCompetencia']);
$router->get('/api/catalogo/periodos', [\App\Controllers\CatalogoController::class, 'obtenerPeriodosYEscalas']);
$router->post('/api/catalogo/periodo', [\App\Controllers\CatalogoController::class, 'guardarPeriodo']);

// 5. Utilidad de diagnóstico de Base de Datos
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
