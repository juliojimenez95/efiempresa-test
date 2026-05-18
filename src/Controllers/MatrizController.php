<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EvaluacionService;
use App\Repositories\Contracts\PeriodoRepositoryInterface;
use App\Repositories\Contracts\EmpleadoRepositoryInterface;
use Throwable;

/**
 * Controlador para la Matriz Resumen de Equipo por puesto, área y periodo.
 */
class MatrizController
{
    private EvaluacionService $evaluacionService;
    private PeriodoRepositoryInterface $periodoRepo;
    private EmpleadoRepositoryInterface $empleadoRepo;

    /**
     * Constructor con autowiring de dependencias.
     */
    public function __construct(
        EvaluacionService $evaluacionService,
        PeriodoRepositoryInterface $periodoRepo,
        EmpleadoRepositoryInterface $empleadoRepo
    ) {
        $this->evaluacionService = $evaluacionService;
        $this->periodoRepo = $periodoRepo;
        $this->empleadoRepo = $empleadoRepo;
    }

    /**
     * Devuelve los datos de la matriz resumen de equipo con filtros.
     * GET /api/matriz?periodo_id=X&area_id=Y
     * 
     * @param array $requestData
     */
    public function obtenerMatriz(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $periodoId = isset($requestData['periodo_id']) ? (int) $requestData['periodo_id'] : 0;
            $areaId    = isset($requestData['area_id']) && $requestData['area_id'] !== '' ? (int) $requestData['area_id'] : null;

            // Si no se define el periodo, resolver el periodo activo actual
            if ($periodoId <= 0) {
                $activo = $this->periodoRepo->findActive();
                if (!$activo) {
                    http_response_code(404);
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'No se ha detectado ningún periodo de evaluación activo.'
                    ], JSON_UNESCAPED_UNICODE);
                    return;
                }
                $periodoId = (int) $activo['id'];
            }

            // Invocar el servicio
            $resultado = $this->evaluacionService->obtenerMatrizResumen($periodoId, $areaId);

            http_response_code(200);
            echo json_encode([
                'status'       => 'success',
                'periodo'      => $resultado['periodo'],
                'matriz'       => $resultado['matriz'],
                'detalles'     => $resultado['detalles'],
                'competencias' => $resultado['competencias']
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al recuperar la matriz de resumen.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Retorna los filtros necesarios (periodos y áreas) de forma dinámica desde base de datos.
     * GET /api/matriz/filtros
     * 
     * @param array $requestData
     */
    public function obtenerFiltros(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $periodos = $this->periodoRepo->findAll();
            $areas    = $this->empleadoRepo->findAreas();

            http_response_code(200);
            echo json_encode([
                'status'   => 'success',
                'periodos' => $periodos,
                'areas'    => $areas
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al cargar los catálogos de filtrado.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
