<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EvaluacionService;
use App\Repositories\Contracts\EmpleadoRepositoryInterface;
use Throwable;

/**
 * Controlador para la Ficha Individual del empleado e histórico de evaluaciones.
 */
class FichaController
{
    private EvaluacionService $evaluacionService;
    private EmpleadoRepositoryInterface $empleadoRepo;

    /**
     * Constructor con autowiring de dependencias.
     */
    public function __construct(
        EvaluacionService $evaluacionService,
        EmpleadoRepositoryInterface $empleadoRepo
    ) {
        $this->evaluacionService = $evaluacionService;
        $this->empleadoRepo = $empleadoRepo;
    }

    /**
     * Obtiene el histórico y detalle del empleado para la ficha de seguimiento individual.
     * GET /api/ficha?empleado_id=X
     * 
     * @param array $requestData
     */
    public function obtenerFicha(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $empleadoId = isset($requestData['empleado_id']) ? (int) $requestData['empleado_id'] : 0;

            if ($empleadoId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Parámetro empleado_id es requerido para recuperar la ficha.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            // Invocar servicio orquestador
            $ficha = $this->evaluacionService->obtenerFichaIndividual($empleadoId);

            http_response_code(200);
            echo json_encode([
                'status'    => 'success',
                'empleado'  => $ficha['empleado'],
                'historial' => $ficha['historial']
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al recuperar la ficha individual del empleado.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Listado general de empleados activos en el ERP.
     * GET /api/empleados
     * 
     * @param array $requestData
     */
    public function obtenerEmpleados(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $empleados = $this->empleadoRepo->findAll();

            http_response_code(200);
            echo json_encode([
                'status'    => 'success',
                'empleados' => $empleados
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al listar los empleados activos.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
