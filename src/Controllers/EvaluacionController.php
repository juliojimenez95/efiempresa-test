<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EvaluacionService;
use App\Repositories\Contracts\EmpleadoRepositoryInterface;
use App\Repositories\Contracts\PeriodoRepositoryInterface;
use App\Repositories\Contracts\PerfilObjetivoRepositoryInterface;
use App\Repositories\Contracts\EvaluacionRepositoryInterface;
use Throwable;

/**
 * Controlador para la pantalla de Evaluación One-to-One y APIs asociadas.
 */
class EvaluacionController
{
    private EvaluacionService $evaluacionService;
    private EmpleadoRepositoryInterface $empleadoRepo;
    private PeriodoRepositoryInterface $periodoRepo;
    private PerfilObjetivoRepositoryInterface $perfilObjetivoRepo;
    private EvaluacionRepositoryInterface $evaluacionRepo;

    /**
     * Inyección por constructor de servicios y repositorios.
     */
    public function __construct(
        EvaluacionService $evaluacionService,
        EmpleadoRepositoryInterface $empleadoRepo,
        PeriodoRepositoryInterface $periodoRepo,
        PerfilObjetivoRepositoryInterface $perfilObjetivoRepo,
        EvaluacionRepositoryInterface $evaluacionRepo
    ) {
        $this->evaluacionService = $evaluacionService;
        $this->empleadoRepo = $empleadoRepo;
        $this->periodoRepo = $periodoRepo;
        $this->perfilObjetivoRepo = $perfilObjetivoRepo;
        $this->evaluacionRepo = $evaluacionRepo;
    }

    /**
     * Carga de datos para renderizar el formulario dinámico del evaluado.
     * GET /api/evaluacion/formulario?empleado_id=X&periodo_id=Y
     * 
     * @param array $requestData
     */
    public function obtenerFormulario(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $empleadoId = isset($requestData['empleado_id']) ? (int) $requestData['empleado_id'] : 0;
            $periodoId  = isset($requestData['periodo_id']) ? (int) $requestData['periodo_id'] : 0;

            if ($empleadoId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Parámetro empleado_id es requerido y debe ser válido.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // 1. Obtener datos del empleado
            $empleado = $this->empleadoRepo->findById($empleadoId);
            if (!$empleado) {
                http_response_code(404);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'El empleado solicitado no existe.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // 2. Obtener periodo (especificado o activo actual)
            $periodo = null;
            if ($periodoId > 0) {
                $periodo = $this->periodoRepo->findById($periodoId);
            } else {
                $periodo = $this->periodoRepo->findActive();
            }

            if (!$periodo) {
                http_response_code(404);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'No se detecta ningún periodo de evaluación activo.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $periodoId = (int) $periodo['id'];

            // 3. Obtener el perfil objetivo ideal del puesto en este periodo
            $perfilIdeal = $this->perfilObjetivoRepo->findByPuestoAndPeriodo((int) $empleado['puesto_id'], $periodoId);

            // 4. Obtener si ya existe una evaluación guardada previa
            $cabecera = $this->evaluacionRepo->findCabecera($empleadoId, $periodoId);
            $evaluacionesGuardadas = [];
            if ($cabecera) {
                $evaluacionesGuardadas = $this->evaluacionRepo->findDetalle((int) $cabecera['id']);
            }

            http_response_code(200);
            echo json_encode([
                'status'   => 'success',
                'empleado' => $empleado,
                'periodo'  => $periodo,
                'perfil'   => $perfilIdeal,
                'guardado' => $cabecera ? [
                    'cabecera' => $cabecera,
                    'detalles' => $evaluacionesGuardadas
                ] : null
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al obtener datos del formulario de evaluación.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Guarda la evaluación y recalcula gaps e índices ponderados.
     * POST /api/evaluacion
     * 
     * @param array $requestData
     */
    public function store(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $empleadoId   = isset($requestData['empleado_id']) ? (int) $requestData['empleado_id'] : 0;
            $periodoId    = isset($requestData['periodo_id']) ? (int) $requestData['periodo_id'] : 0;
            $puntuaciones = $requestData['puntuaciones'] ?? [];
            $comentarios  = $requestData['comentarios'] ?? [];
            $evaluadoPor  = isset($requestData['evaluado_por']) ? trim($requestData['evaluado_por']) : 'Evaluador';

            if ($empleadoId <= 0 || $periodoId <= 0 || empty($puntuaciones)) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Datos insuficientes. Se requiere empleado_id, periodo_id y puntuaciones.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Delegar toda la transacción y cálculos complejos al orquestador de Servicios
            $resultado = $this->evaluacionService->registrarEvaluacion([
                'empleado_id'  => $empleadoId,
                'periodo_id'   => $periodoId,
                'puntuaciones' => $puntuaciones,
                'comentarios'  => $comentarios,
                'evaluado_por' => $evaluadoPor
            ]);

            http_response_code(200);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
