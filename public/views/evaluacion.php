<?php
declare(strict_types=1);

/**
 * Vista de Evaluación One-to-One
 * Módulo: Gestor de Competencias ERP
 */

// Obtener el contenedor global de dependencias para pre-cargar los empleados en el servidor
$container = \App\Core\Container::getInstance();
$empleadoRepo = $container->get(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class);

try {
    $empleados = $empleadoRepo->findAll();
} catch (\Throwable $e) {
    $empleados = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluación One-to-One | Gestor de Competencias</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Hojas de estilos del módulo -->
    <link rel="stylesheet" href="/css/evaluacion.css">
</head>
<body class="py-4">

    <!-- Notificaciones flotantes tipo Toast -->
    <div id="toast-container"></div>

    <div class="container-fluid max-width-lg px-4">
        <!-- Navegación superior interna -->
        <header class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-uppercase tracking-wider text-muted fw-semibold small">Evaluación del Rendimiento</span>
                <h1 class="h3 fw-bold m-0 text-slate-800">Panel One-to-One</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="/matriz" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Matriz Resumen
                </a>
                <a href="/catalogo" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-gear-fill me-1"></i> Configuración
                </a>
            </div>
        </header>

        <div class="row g-4">
            <!-- PANEL IZQUIERDO: Datos de la Evaluación -->
            <aside class="col-lg-4">
                <div class="card app-card p-4">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                        <i class="bi bi-person-badge-fill me-2 text-indigo"></i>Ficha del Colaborador
                    </h5>

                    <!-- Selector de Empleado (Pre-renderizado en servidor para mejor rendimiento y SEO) -->
                    <div class="mb-3">
                        <label for="empleado-select" class="form-label fw-semibold text-secondary">Colaborador a Evaluar</label>
                        <select id="empleado-select" class="form-select border-slate-300">
                            <option value="">-- Selecciona un colaborador --</option>
                            <?php foreach ($empleados as $e): ?>
                                <option value="<?= (int) $e['id'] ?>">
                                    <?= htmlspecialchars($e['apellidos'] . ', ' . $e['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Campos descriptivos auto-completables (deshabilitados) -->
                    <div class="mb-3">
                        <label for="puesto-input" class="form-label fw-semibold text-secondary">Puesto / Cargo</label>
                        <input type="text" id="puesto-input" class="form-control" disabled placeholder="Pendiente de selección...">
                    </div>

                    <div class="mb-3">
                        <label for="evaluador-input" class="form-label fw-semibold text-secondary">Evaluador Autorizado</label>
                        <input type="text" id="evaluador-input" class="form-control" disabled placeholder="Autodetectado...">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="periodo-input" class="form-label fw-semibold text-secondary">Periodo Activo</label>
                            <input type="text" id="periodo-input" class="form-control" disabled placeholder="Qx-202x">
                        </div>
                        <div class="col-md-6">
                            <label for="tipo-input" class="form-label fw-semibold text-secondary">Tipo de Feedback</label>
                            <input type="text" id="tipo-input" class="form-control" disabled value="One to one mensual">
                        </div>
                    </div>

                    <!-- Directrices del Candidato (Caja informativa premium) -->
                    <div class="mt-4">
                        <label class="form-label fw-bold text-secondary">Qué debe explicar el candidato</label>
                        <div class="instrucciones-caja">
                            <strong>Directrices de la Conversación:</strong><br>
                            <span class="d-block mt-1">• ¿Cómo se ha sentido en el cumplimiento de sus objetivos?</span>
                            <span class="d-block mt-1">• Obstáculos encontrados y apoyos requeridos del líder.</span>
                            <span class="d-block mt-1">• Propuestas de mejora para agilizar la operativa del área.</span>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- PANEL DERECHO: Bloque Competencial Dinámico -->
            <main class="col-lg-8">
                <div class="card app-card p-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-4 border-bottom pb-3">
                        <h4 class="fw-bold m-0 text-slate-800">
                            Bloque competencial · Nómina y operativa
                        </h4>
                        <!-- Leyenda Horizontal de Badges -->
                        <div class="d-flex gap-2 align-items-center mt-2 mt-md-0">
                            <span class="small text-muted fw-medium me-1">Leyenda:</span>
                            <span class="badge bg-danger-subtle text-danger font-monospace px-2 py-1">1-2 Bajo</span>
                            <span class="badge bg-warning-subtle text-warning font-monospace px-2 py-1">3 Medio</span>
                            <span class="badge bg-success-subtle text-success font-monospace px-2 py-1">4-5 Alto</span>
                        </div>
                    </div>

                    <!-- Empty State Placeholder (Cuando no hay nadie seleccionado) -->
                    <div id="empty-state" class="text-center py-5">
                        <i class="bi bi-search text-muted display-4"></i>
                        <h5 class="fw-bold mt-3 text-slate-600">Ningún Colaborador Seleccionado</h5>
                        <p class="text-muted max-width-xs mx-auto">
                            Selecciona un empleado de la lista de la izquierda para cargar sus objetivos y calificaciones históricas en tiempo real.
                        </p>
                    </div>

                    <!-- Contenedor de Competencias Dinámicas (Inyectadas mediante JS) -->
                    <div id="competencias-container" class="d-none">
                        <!-- Las filas dinámicas se renderizan aquí -->
                    </div>

                    <!-- Pie de Bloque Informativo / Métricas en Vivo -->
                    <div class="metricas-footer-bar mt-4 d-flex justify-content-between align-items-center d-none" id="footer-metricas">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                            <span class="small">Cambios sincronizados asíncronamente con cálculo de gaps.</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-uppercase tracking-wider small fw-semibold text-sky-800">Ajuste Global:</span>
                            <span class="badge bg-primary fs-6 px-3 py-2" id="badge-ajuste-global">0.0%</span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Script de lógica de interfaz dinámica -->
    <script src="/js/evaluacion.js" defer></script>
</body>
</html>
