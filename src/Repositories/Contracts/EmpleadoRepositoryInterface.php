<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Contrato para el repositorio de Empleados.
 * Define los métodos de acceso para los datos del personal y sus cargos.
 */
interface EmpleadoRepositoryInterface
{
    /**
     * Obtiene la lista completa de empleados activos en el ERP, incluyendo
     * su puesto de trabajo y departamento (área).
     * 
     * @return array
     */
    public function findAll(): array;

    /**
     * Obtiene el perfil de un empleado específico por su ID.
     * 
     * @param int $id
     * @return array|null
     */
    public function findById(int $id): ?array;

    /**
     * Obtiene todos los empleados de un departamento (área) específico.
     * 
     * @param int $areaId
     * @return array
     */
    public function findByArea(int $areaId): array;

    /**
     * Obtiene todas las áreas o departamentos registrados.
     * 
     * @return array
     */
    public function findAreas(): array;
}
