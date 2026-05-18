<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Contrato para el repositorio de Periodos.
 * Define la persistencia e histórico de ciclos evaluativos y sus escalas de valoración.
 */
interface PeriodoRepositoryInterface
{
    /**
     * Obtiene todos los periodos registrados junto con la escala de valoración configurada.
     * 
     * @return array
     */
    public function findAll(): array;

    /**
     * Obtiene un periodo específico por su ID con su respectiva escala.
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * Obtiene el periodo activo/abierto del ERP actual.
     * Retorna null si no hay ningún periodo abierto.
     * 
     * @return array|null
     */
    public function findActive(): ?array;

    /**
     * Obtiene todas las escalas de valoración registradas en base de datos.
     * 
     * @return array
     */
    public function findEscalas(): array;

    /**
     * Registra un nuevo periodo de evaluación en BD.
     * 
     * @param array $data
     * @return int ID del periodo creado
     */
    public function create(array $data): int;

    /**
     * Modifica datos del periodo (incluye activar/desactivar y marcar como cerrado).
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
}
