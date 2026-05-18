<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Contrato para el repositorio de Perfiles Objetivo (Cargos ideales).
 * Gestiona el mapeo de competencias requeridas, pesos y valores ideales por puesto y periodo.
 */
interface PerfilObjetivoRepositoryInterface
{
    /**
     * Obtiene las competencias, valores ideales y pesos configurados para un puesto
     * en un periodo de evaluación específico.
     * 
     * @param int $puestoId
     * @param int $periodoId
     * @return array
     */
    public function findByPuestoAndPeriodo(int $puestoId, int $periodoId): array;

    /**
     * Asocia una competencia con sus valores ideales al perfil de un puesto para un periodo.
     * 
     * @param array $data
     * @return int ID de la asociación creada
     */
    public function create(array $data): int;

    /**
     * Actualiza el valor ideal o el peso de una competencia en el perfil objetivo.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina una competencia específica del perfil de un puesto para un periodo.
     * 
     * @param int $puestoId
     * @param int $competenciaId
     * @param int $periodoId
     * @return bool
     */
    public function deleteCompetencia(int $puestoId, int $competenciaId, int $periodoId): bool;
}
