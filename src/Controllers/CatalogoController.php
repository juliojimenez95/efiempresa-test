<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\Contracts\CompetenciaRepositoryInterface;
use App\Repositories\Contracts\PeriodoRepositoryInterface;
use Throwable;

/**
 * Controlador para la gestión y CRUD de la parametrización de catálogos (competencias, bloques, escalas).
 */
class CatalogoController
{
    private CompetenciaRepositoryInterface $competenciaRepo;
    private PeriodoRepositoryInterface $periodoRepo;

    /**
     * Inyección por constructor.
     */
    public function __construct(
        CompetenciaRepositoryInterface $competenciaRepo,
        PeriodoRepositoryInterface $periodoRepo
    ) {
        $this->competenciaRepo = $competenciaRepo;
        $this->periodoRepo = $periodoRepo;
    }

    /**
     * Devuelve las competencias y familias de competencias.
     * GET /api/catalogo/competencias
     * 
     * @param array $requestData
     */
    public function obtenerCompetencias(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $competencias = $this->competenciaRepo->findAll();
            $bloques      = $this->competenciaRepo->findBloques();

            http_response_code(200);
            echo json_encode([
                'status'       => 'success',
                'competencias' => $competencias,
                'bloques'      => $bloques
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al recuperar el catálogo de competencias.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Guarda (crea o actualiza) una competencia.
     * POST /api/catalogo/competencia
     * 
     * @param array $requestData
     */
    public function guardarCompetencia(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id          = isset($requestData['id']) ? (int) $requestData['id'] : 0;
            $nombre      = isset($requestData['nombre']) ? trim($requestData['nombre']) : '';
            $descripcion = isset($requestData['descripcion']) ? trim($requestData['descripcion']) : '';
            $bloqueId    = isset($requestData['bloque_id']) ? (int) $requestData['bloque_id'] : 0;
            $activo      = isset($requestData['activo']) ? (int) $requestData['activo'] : 1;

            if ($nombre === '' || $bloqueId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'El nombre de la competencia y el bloque/familia son requeridos.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $datos = [
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'bloque_id'   => $bloqueId,
                'activo'      => $activo
            ];

            if ($id > 0) {
                $this->competenciaRepo->update($id, $datos);
                $mensaje = 'Competencia actualizada exitosamente, socio.';
            } else {
                $id = $this->competenciaRepo->create($datos);
                $mensaje = 'Competencia creada exitosamente, socio.';
            }

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => $mensaje,
                'id'      => $id
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al guardar la competencia.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Realiza la eliminación lógica (desactivación) de una competencia.
     * POST /api/catalogo/competencia/eliminar
     * 
     * @param array $requestData
     */
    public function eliminarCompetencia(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = isset($requestData['id']) ? (int) $requestData['id'] : 0;

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'ID de competencia no válido para eliminar.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $this->competenciaRepo->delete($id);

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Competencia desactivada exitosamente del ERP.'
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al eliminar la competencia.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Devuelve los periodos registrados y las escalas de valoración del sistema.
     * GET /api/catalogo/periodos
     * 
     * @param array $requestData
     */
    public function obtenerPeriodosYEscalas(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $periodos = $this->periodoRepo->findAll();
            $escalas  = $this->periodoRepo->findEscalas();

            http_response_code(200);
            echo json_encode([
                'status'   => 'success',
                'periodos' => $periodos,
                'escalas'  => $escalas
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al recuperar periodos y escalas.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Guarda (crea o actualiza) un periodo evaluativo.
     * POST /api/catalogo/periodo
     * 
     * @param array $requestData
     */
    public function guardarPeriodo(array $requestData): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id                 = isset($requestData['id']) ? (int) $requestData['id'] : 0;
            $nombre             = isset($requestData['nombre']) ? trim($requestData['nombre']) : '';
            $fechaInicio        = isset($requestData['fecha_inicio']) ? trim($requestData['fecha_inicio']) : '';
            $fechaFin           = isset($requestData['fecha_fin']) ? trim($requestData['fecha_fin']) : '';
            $escalaValoracionId = isset($requestData['escala_valoracion_id']) ? (int) $requestData['escala_valoracion_id'] : 0;
            $activo             = isset($requestData['activo']) ? (int) $requestData['activo'] : 1;
            $cerrado            = isset($requestData['cerrado']) ? (int) $requestData['cerrado'] : 0;

            if ($nombre === '' || $fechaInicio === '' || $fechaFin === '' || $escalaValoracionId <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'El nombre, fechas y escala del periodo son obligatorios.'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $datos = [
                'nombre'               => $nombre,
                'fecha_inicio'         => $fechaInicio,
                'fecha_fin'            => $fechaFin,
                'escala_valoracion_id' => $escalaValoracionId,
                'activo'               => $activo,
                'cerrado'              => $cerrado
            ];

            if ($id > 0) {
                $this->periodoRepo->update($id, $datos);
                $mensaje = 'Periodo actualizado correctamente, parce.';
            } else {
                $id = $this->periodoRepo->create($datos);
                $mensaje = 'Periodo de evaluación creado correctamente, parce.';
            }

            http_response_code(200);
            echo json_encode([
                'status'  => 'success',
                'message' => $mensaje,
                'id'      => $id
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error al guardar el periodo de evaluación.',
                'details' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
