<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Contrato para el repositorio de Competencias.
 * Define las operaciones permitidas sobre el catálogo maestro de competencias y bloques.
 */
interface CompetenciaRepositoryInterface
{
    /**
     * Obtiene todas las competencias activas con la información de su bloque/familia.
     * 
     * @return array
     */
    public function findAll(): array;

    /**
     * Obtiene una competencia por su ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * Obtiene todas las familias/bloques de competencias activos.
     * 
     * @return array
     */
    public function findBloques(): array;

    /**
     * Crea una nueva competencia en el catálogo.
     * 
     * @param array $data
     * @return int ID de la competencia creada
     */
    public function create(array $data): int;

    /**
     * Actualiza los datos de una competencia existente.
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina (o desactiva de forma lógica) una competencia del catálogo.
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
