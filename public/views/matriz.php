<?php
declare(strict_types=1);

/**
 * Vista de Matriz Resumen de Competencias por Equipo
 * Módulo: Gestor de Competencias ERP
 */

$container = \App\Core\Container::getInstance();
$periodoRepo = $container->get(\App\Repositories\Contracts\PeriodoRepositoryInterface::class);
$empleadoRepo = $container->get(\App\Repositories\Contracts\EmpleadoRepositoryInterface::class);

try {
    $periodos = $periodoRepo->findAll();
    $areas = $empleadoRepo->findAreas();
    $activo = $periodoRepo->findActive();
    $activoId = $activo ? (int) $activo['id'] : (count($periodos) > 0 ? (int) $periodos[0]['id'] : 0);
} catch (\Throwable $e) {
    $periodos = [];
    $areas = [];
    $activoId = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matriz de Competencias | Gestor de Competencias</title>
    <!-- Bootstrap 5 CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Hojas de estilos del módulo -->
    <link rel="stylesheet" href="/css/evaluacion.css">
    <link rel="stylesheet" href="/css/matriz.css">
</head>
<body class="py-4">

    <!-- Contenedor para notificaciones Toast flotantes -->
    <div id="toast-container"></div>

    <div class="container-fluid max-width-lg px-4">
        <!-- Cabecera de Navegación -->
        <header class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="text-uppercase tracking-wider text-muted fw-semibold small">Líderes de Equipo & HR</span>
                <h1 class="h3 fw-bold m-0 text-slate-800">Matriz Resumen y Mapa de Calor</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="/evaluacion" class="btn btn-indigo btn-sm text-white" style="background-color: #4f46e5;">
                    <i class="bi bi-person-fill-gear me-1"></i> Evaluar Colaborador
                </a>
                <a href="/ficha" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-person-badge-fill me-1"></i> Ficha Individual
                </a>
                <a href="/catalogo" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-gear-fill me-1"></i> Configuración
                </a>
            </div>
        </header>

        <!-- Sección de Filtros Superiores (Responsivos e Instantáneos) -->
        <div class="card app-card p-4 mb-4">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label for="periodo-select" class="form-label fw-bold text-secondary small">Periodo Activo</label>
                    <select id="periodo-select" class="form-select border-slate-300">
                        <?php foreach ($periodos as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === $activoId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                <?= (int) $p['activo'] === 1 ? ' (Activo)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="area-select" class="form-label fw-bold text-secondary small">Área o Departamento</label>
                    <select id="area-select" class="form-select border-slate-300">
                        <option value="">-- Todas las áreas --</option>
                        <?php foreach ($areas as $a): ?>
                            <option value="<?= (int) $a['id'] ?>">
                                <?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <button id="btn-exportar" class="btn btn-outline-success btn-sm w-100 py-2 d-none">
                        <i class="bi bi-file-earmark-excel-fill me-1"></i> Exportar Datos
                    </button>
                </div>
            </div>
        </div>

        <!-- Dashboard Directivo y Métricas Resumen -->
        <div class="row g-3 mb-4" id="dashboard-resumen">
            <div class="col-6 col-lg-3">
                <div class="card card-resumen-matriz p-3 text-center">
                    <span class="text-uppercase tracking-wider text-muted small fw-bold">Colaboradores</span>
                    <h2 class="fw-bold mt-1 text-slate-800" id="stat-colaboradores">0</h2>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-resumen-matriz p-3 text-center">
                    <span class="text-uppercase tracking-wider text-muted small fw-bold">Evaluados</span>
                    <h2 class="fw-bold mt-1 text-success" id="stat-evaluados">0</h2>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-resumen-matriz p-3 text-center">
                    <span class="text-uppercase tracking-wider text-muted small fw-bold">Ajuste Promedio</span>
                    <h2 class="fw-bold mt-1 text-indigo" id="stat-promedio" style="color: #4f46e5;">0.0%</h2>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card card-resumen-matriz p-3 text-center">
                    <span class="text-uppercase tracking-wider text-muted small fw-bold">Bajo Desempeño</span>
                    <h2 class="fw-bold mt-1 text-danger" id="stat-criticos">0</h2>
                </div>
            </div>
        </div>

        <!-- Contenedor Principal de la Matriz Cruzada -->
        <div class="matriz-table-container">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-matriz" id="matriz-competencias-tabla">
                    <thead class="table-light text-secondary" id="matriz-tabla-cabecera">
                        <!-- Generado dinámicamente por JS -->
                    </thead>
                    <tbody id="matriz-tabla-cuerpo">
                        <!-- Generado dinámicamente por JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Empty State en caso de no haber datos -->
        <div id="matriz-empty-state" class="card app-card p-5 text-center d-none mt-4">
            <i class="bi bi-grid-3x3 text-muted display-4"></i>
            <h4 class="fw-bold mt-3 text-slate-700">Sin colaboradores registrados</h4>
            <p class="text-muted max-width-sm mx-auto">
                No hay empleados activos registrados para el departamento o periodo seleccionado. Verifica los filtros superiores.
            </p>
        </div>
    </div>

    <!-- Script JavaScript para manipulación interactiva de DOM -->
    <script src="/js/matriz.js" defer></script>
</body>
</html>
