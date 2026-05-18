<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Enrutador Centralizado para capturar peticiones HTTP.
 * Mapea métodos (GET/POST) y URIs limpias a controladores y métodos específicos.
 */
class Router
{
    /** @var array<string, array<string, array|callable>> */
    private array $routes = [];

    /**
     * Registra una ruta para el método GET.
     * 
     * @param string $path
     * @param array|callable $handler
     * @return self
     */
    public function get(string $path, $handler): self
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Registra una ruta para el método POST.
     * 
     * @param string $path
     * @param array|callable $handler
     * @return self
     */
    public function post(string $path, $handler): self
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Añade internamente la ruta normalizando el path.
     * 
     * @param string $method
     * @param string $path
     * @param array|callable $handler
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $normalizedPath = '/' . trim($path, '/');
        $this->routes[$method][$normalizedPath] = $handler;
    }

    /**
     * Despacha la petición capturando el método y URI actual, buscando coincidencias en la tabla de rutas.
     */
    public function dispatch(Container $container): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        // Extraer la ruta limpia ignorando query parameters
        $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

        // Hacer el enrutador portable detectando subdirectorios en local
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);

        // Limpiar el basePath de la ruta si no estamos en la raíz del dominio
        if ($basePath !== '/' && $basePath !== '\\' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        $normalizedPath = '/' . trim($path, '/');

        // Buscar coincidencia exacta en la tabla de rutas
        $handler = $this->routes[$method][$normalizedPath] ?? null;

        if ($handler === null) {
            $this->handleNotFound();
            return;
        }

        // Si es un closure directo, se ejecuta
        if (is_callable($handler)) {
            try {
                $handler();
            } catch (\Throwable $e) {
                $this->handleError($e->getMessage(), $e);
            }
            return;
        }

        // Si es un array de mapeo [ControllerClass, MethodName]
        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $methodName] = $handler;

            try {
                // Instanciar y auto-resolver el controlador con sus dependencias vía Container
                $controller = $container->get($controllerClass);

                if (!method_exists($controller, $methodName)) {
                    $this->handleError(
                        sprintf("Método no encontrado: El método '%s' no existe en el controlador '%s'.", $methodName, $controllerClass)
                    );
                    return;
                }

                // Obtener datos unificados del Request (GET, POST o JSON)
                $requestData = $this->getRequestData();

                // Ejecutar la acción del controlador inyectando los datos limpios
                $controller->$methodName($requestData);

            } catch (\Throwable $e) {
                $this->handleError($e->getMessage(), $e);
            }
        }
    }

    /**
     * Obtiene y unifica los datos de la petición (JSON payload o parámetros estándar).
     */
    private function getRequestData(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            
            // Si la petición viene en JSON (Fetch API estándar en frontend-spec)
            if (strpos($contentType, 'application/json') !== false) {
                $input = file_get_contents('php://input');
                return json_decode($input, true) ?? [];
            }
            
            return $_POST;
        }

        return $_GET;
    }

    /**
     * Verifica si la petición actual espera una respuesta AJAX/JSON.
     */
    private function isAjaxRequest(): bool
    {
        // 1. Cabecera estándar de peticiones asíncronas
        $headers = getallheaders();
        $requestedWith = $headers['X-Requested-With'] ?? $headers['x-requested-with'] ?? '';
        if (strtolower($requestedWith) === 'xmlhttprequest') {
            return true;
        }

        // 2. Cabecera Accept
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        // 3. Convención de rutas de API
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($requestUri, '/api/') !== false;
    }

    /**
     * Maneja el error 404 (Ruta No Encontrada) de forma amigable o asíncrona.
     */
    private function handleNotFound(): void
    {
        http_response_code(404);

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'code'    => 404,
                'message' => 'La ruta o recurso solicitado no existe.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Si es navegación normal, renderizamos una vista básica de 404
        $view404 = dirname(__DIR__, 2) . '/public/views/error404.php';
        if (file_exists($view404)) {
            require_once $view404;
        } else {
            echo '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>404 Not Found</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light d-flex align-items-center justify-content-center vh-100">
                <div class="text-center bg-white p-5 rounded shadow-sm" style="max-width: 500px;">
                    <h1 class="display-1 text-danger fw-bold">404</h1>
                    <h2 class="fs-4 text-dark mb-3">Página No Encontrada</h2>
                    <p class="text-secondary mb-4">La ruta especificada no existe en este módulo, parce.</p>
                    <a href="./" class="btn btn-primary px-4">Ir al Inicio</a>
                </div>
            </body>
            </html>';
        }
    }

    /**
     * Maneja excepciones o fallos en el ciclo de ejecución del despachador de forma controlada.
     */
    private function handleError(string $message, ?\Throwable $exception = null): void
    {
        http_response_code(500);

        if ($exception !== null) {
            error_log(sprintf("[RouterExecutionError] %s\nTrace: %s", $exception->getMessage(), $exception->getTraceAsString()));
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'error',
                'code'    => 500,
                'message' => 'Error interno del servidor al procesar la petición.',
                'debug'   => $message // En producción se ocultaría, ideal para este ejercicio de pruebas técnicas
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo sprintf(
            '<!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>500 Internal Error</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            </head>
            <body class="bg-light d-flex align-items-center justify-content-center vh-100">
                <div class="bg-white p-5 rounded shadow-sm w-100" style="max-width: 800px;">
                    <h1 class="display-5 text-danger fw-bold mb-3">500 Error Interno</h1>
                    <p class="text-secondary mb-3">Ocurrió un error inesperado al despachar la ruta, parce:</p>
                    <div class="alert alert-warning font-monospace p-3 small">%s</div>
                    <a href="./" class="btn btn-secondary px-4">Ir al Inicio</a>
                </div>
            </body>
            </html>',
            htmlspecialchars($message)
        );
    }
}
