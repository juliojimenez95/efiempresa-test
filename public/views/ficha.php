<?php
declare(strict_types=1);

/**
 * Vista de Ficha Individual e Histórico de Competencias
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
    <title>Ficha de Talento | Gestor de Competencias</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Hojas de estilos del módulo -->
    <link rel="stylesheet" href="/css/evaluacion.css">
    <link rel="stylesheet" href="/css/ficha.css">
</head>
<body class="py-4">

    <!-- Notificaciones flotantes tipo Toast -->
    <div id="toast-container"></div>

    <div class="container-fluid max-width-lg px-4">
        <!-- Navegación superior interna -->
        <header class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-uppercase tracking-wider text-muted fw-semibold small">Talento y Desarrollo</span>
                <h1 class="h3 fw-bold m-0 text-slate-800">Ficha Individual de Competencias</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="/evaluacion" class="btn btn-indigo btn-sm text-white" style="background-color: #4f46e5;">
                    <i class="bi bi-person-fill-gear me-1"></i> Nueva Evaluación
                </a>
                <a href="/matriz" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-grid-3x3-gap-fill me-1"></i> Matriz Resumen
                </a>
            </div>
        </header>

        <div class="row g-4">
            <!-- PANEL IZQUIERDO: Selector de Talento, Ficha y Timeline Histórico -->
            <aside class="col-lg-4">
                <!-- Selector de Empleado -->
                <div class="card app-card p-4 mb-4">
                    <h5 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                        <i class="bi bi-search me-2 text-indigo"></i>Búsqueda de Talento
                    </h5>
                    <div class="mb-2">
                        <label for="empleado-select" class="form-label fw-semibold text-secondary">Selecciona un Colaborador</label>
                        <select id="empleado-select" class="form-select border-slate-300">
                            <option value="">-- Elige un colaborador --</option>
                            <?php foreach ($empleados as $e): ?>
                                <option value="<?= (int) $e['id'] ?>">
                                    <?= htmlspecialchars($e['apellidos'] . ', ' . $e['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Detalle del Perfil Seleccionado (Dinámico por JS) -->
                <div id="ficha-perfil-container" class="card ficha-perfil-card p-4 mb-4 d-none">
                    <div class="d-flex align-items-center gap-3">
                        <div class="ficha-perfil-avatar" id="avatar-iniciales">
                            JR
                        </div>
                        <div>
                            <h5 class="fw-bold m-0" id="perfil-nombre">Julián Ramírez</h5>
                            <span class="text-sky-400 small" id="perfil-puesto">Senior Software Architect</span>
                        </div>
                    </div>
                    <div class="mt-3 border-top border-secondary pt-3 fs-7 text-slate-300">
                        <p class="mb-1"><i class="bi bi-envelope-fill me-2 text-sky-400"></i><span id="perfil-email">julian@efiempresa.com</span></p>
                        <p class="mb-0"><i class="bi bi-building-fill me-2 text-sky-400"></i>Área: <span id="perfil-area">I+D</span></p>
                    </div>
                </div>

                <!-- Línea de Tiempo Histórica -->
                <div id="ficha-historico-card" class="card app-card p-4 d-none">
                    <h6 class="fw-bold mb-3 pb-2 border-bottom text-dark">
                        <i class="bi bi-clock-history me-2 text-indigo"></i>Historial de Evaluaciones
                    </h6>
                    <div class="timeline" id="timeline-container">
                        <!-- Las evaluaciones históricas se renderizan aquí -->
                    </div>
                </div>
            </aside>

            <!-- PANEL DERECHO: Detalle del Periodo Seleccionado & Tabla Comparativa -->
            <main class="col-lg-8">
                <!-- Dropdown de Periodo superior -->
                <div class="card app-card p-4 mb-4 d-none" id="periodo-filtro-card">
                    <div class="row align-items-center g-3">
                        <div class="col-md-7">
                            <h5 class="fw-bold m-0 text-slate-800">Comparación Ideal vs Actual</h5>
                            <p class="text-muted small m-0 mt-1">Filtra las métricas por periodo para evaluar la brecha de competencias.</p>
                        </div>
                        <div class="col-md-5">
                            <label for="periodo-select" class="form-label fw-semibold text-secondary small mb-1">Periodo Evaluado</label>
                            <select id="periodo-select" class="form-select border-slate-300">
                                <!-- Llenado por JS -->
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Empty State Principal (Cuando no hay empleado seleccionado) -->
                <div id="ficha-empty-state" class="card app-card p-5 text-center py-5">
                    <i class="bi bi-person-bounding-box text-muted display-3"></i>
                    <h4 class="fw-bold mt-3 text-slate-600">Visualizador de Competencias e Históricos</h4>
                    <p class="text-muted max-width-sm mx-auto">
                        Selecciona un colaborador del buscador de talento a la izquierda para cargar su perfil corporativo, línea de evolución temporal y brechas competenciales.
                    </p>
                </div>

                <!-- Detalle Analítico de Tabla Comparativa -->
                <div id="ficha-analisis-container" class="card app-card p-4 d-none">
                    <!-- Cabecera de la evaluación del periodo -->
                    <div class="row mb-4 align-items-center pb-3 border-bottom g-3">
                        <div class="col-md-8">
                            <h4 class="fw-bold text-slate-800 mb-1" id="evaluacion-periodo-titulo">
                                Periodo: Q1-2026
                            </h4>
                            <p class="text-muted small m-0">
                                Evaluado por: <strong id="evaluacion-evaluador">Julián Ramírez</strong>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <div class="p-3 bg-light rounded border text-center">
                                <span class="text-uppercase tracking-wider text-muted small fw-bold d-block mb-1">Ajuste Global Ponderado</span>
                                <span class="badge bg-success fs-5 px-3 py-2" id="evaluacion-ajuste-global">92.5%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla Comparativa 100% Alineada con el Briefing -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle border-slate-100" id="tabla-ficha-competencias">
                            <thead class="table-light text-secondary fw-semibold">
                                <tr>
                                    <th class="ps-3" style="width: 30%;">Competencia</th>
                                    <th style="width: 10%; text-align: center;">Peso</th>
                                    <th style="width: 12%; text-align: center;">Valor Ideal</th>
                                    <th style="width: 12%; text-align: center;">Valor Real</th>
                                    <th style="width: 10%; text-align: center;">GAP (Brecha)</th>
                                    <th style="width: 12%; text-align: center;">Semáforo</th>
                                    <th class="pe-3" style="width: 14%; text-align: center;">% Ajuste</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-cuerpo-ficha">
                                <!-- Filas dinámicas inyectadas por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Script de lógica de interfaz dinámica -->
    <script src="/js/ficha.js" defer></script>
</body>
</html>
