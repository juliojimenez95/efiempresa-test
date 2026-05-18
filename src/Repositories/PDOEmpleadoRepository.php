<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\EmpleadoRepositoryInterface;
use PDO;

/**
 * Implementación del repositorio de Empleados utilizando PDO.
 */
class PDOEmpleadoRepository implements EmpleadoRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $sql = 'SELECT e.id, e.area_id, e.puesto_id, e.nombre, e.apellidos, e.email, e.fecha_ingreso, e.activo, e.created_at,
                       p.nombre AS puesto_nombre, a.nombre AS area_nombre
                FROM empleados e
                INNER JOIN puestos p ON e.puesto_id = p.id
                INNER JOIN areas a ON e.area_id = a.id
                WHERE e.activo = 1
                ORDER BY e.apellidos ASC, e.nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT e.id, e.area_id, e.puesto_id, e.nombre, e.apellidos, e.email, e.fecha_ingreso, e.activo, e.created_at,
                       p.nombre AS puesto_nombre, a.nombre AS area_nombre
                FROM empleados e
                INNER JOIN puestos p ON e.puesto_id = p.id
                INNER JOIN areas a ON e.area_id = a.id
                WHERE e.id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findByArea(int $areaId): array
    {
        $sql = 'SELECT e.id, e.area_id, e.puesto_id, e.nombre, e.apellidos, e.email, e.fecha_ingreso, e.activo, e.created_at,
                       p.nombre AS puesto_nombre, a.nombre AS area_nombre
                FROM empleados e
                INNER JOIN puestos p ON e.puesto_id = p.id
                INNER JOIN areas a ON e.area_id = a.id
                WHERE e.area_id = :area_id AND e.activo = 1
                ORDER BY e.apellidos ASC, e.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['area_id' => $areaId]);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findAreas(): array
    {
        $sql = 'SELECT id, nombre, descripcion, activo, created_at
                FROM areas
                WHERE activo = 1
                ORDER BY nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
