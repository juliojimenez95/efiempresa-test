<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Contrato para el repositorio de Evaluaciones.
 * Define la persistencia para cabeceras y detalles de las evaluaciones de competencias.
 */
interface EvaluacionRepositoryInterface
{
    /**
     * Obtiene la cabecera de evaluación de un empleado en un periodo determinado.
     * Retorna null si el empleado no ha sido evaluado en ese periodo.
     * 
     * @param int $empleadoId
     * @param int $periodoId
     * @return array|null
     */
    public function findCabecera(int $empleadoId, int $periodoId): ?array;

    /**
     * Obtiene las competencias evaluadas, puntuaciones y brechas (gap)
     * asociadas a una cabecera de evaluación específica.
     * 
     * @param int $cabeceraId
     * @return array
     */
    public function findDetalle(int $cabeceraId): array;

    /**
     * Crea o actualiza la cabecera de evaluación para un empleado y periodo.
     * Retorna el ID de la cabecera.
     * 
     * @param array $data
     * @return int ID de la cabecera creada/actualizada
     */
    public function saveCabecera(array $data): int;

    /**
     * Guarda o actualiza la calificación de una competencia en el detalle de la evaluación.
     * 
     * @param array $data
     * @return bool
     */
    public function saveDetalle(array $data): bool;

    /**
     * Obtiene la ficha histórica de evaluaciones de un empleado.
     * Recupera el porcentaje de ajuste global y los periodos evaluados.
     * 
     * @param int $empleadoId
     * @return array
     */
    public function findHistorialByEmpleado(int $empleadoId): array;

    /**
     * Recupera la información unificada de la Matriz Resumen de equipo.
     * Combina empleados, puestos, y sus calificaciones para un periodo específico,
     * permitiendo opcionalmente filtrar por área/departamento.
     * 
     * @param int $periodoId
     * @param int|null $areaId Filtro por área del ERP opcional
     * @return array
     */
    public function findMatrizResumen(int $periodoId, ?int $areaId = null): array;

    /**
     * Obtiene todos los puntajes y semáforos individuales de competencias para un periodo y área.
     *
     * @param int $periodoId
     * @param int|null $areaId
     * @return array
     */
    public function findMatrizDetalles(int $periodoId, ?int $areaId = null): array;
}
