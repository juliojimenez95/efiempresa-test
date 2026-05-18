<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\PeriodoRepositoryInterface;
use PDO;

/**
 * Implementación del repositorio de Periodos utilizando PDO.
 */
class PDOPeriodoRepository implements PeriodoRepositoryInterface
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
        $sql = 'SELECT p.id, p.escala_id, p.nombre, p.fecha_inicio, p.fecha_fin, p.activo, p.cerrado, p.created_at,
                       e.nombre AS escala_nombre, e.valor_min AS escala_min, e.valor_max AS escala_max
                FROM periodos p
                INNER JOIN escalas_valoracion e ON p.escala_id = e.id
                ORDER BY p.fecha_inicio DESC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT p.id, p.escala_id, p.nombre, p.fecha_inicio, p.fecha_fin, p.activo, p.cerrado, p.created_at,
                       e.nombre AS escala_nombre, e.valor_min AS escala_min, e.valor_max AS escala_max
                FROM periodos p
                INNER JOIN escalas_valoracion e ON p.escala_id = e.id
                WHERE p.id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findActive(): ?array
    {
        $sql = 'SELECT p.id, p.escala_id, p.nombre, p.fecha_inicio, p.fecha_fin, p.activo, p.cerrado, p.created_at,
                       e.nombre AS escala_nombre, e.valor_min AS escala_min, e.valor_max AS escala_max
                FROM periodos p
                INNER JOIN escalas_valoracion e ON p.escala_id = e.id
                WHERE p.activo = 1 AND p.cerrado = 0
                ORDER BY p.fecha_inicio DESC
                LIMIT 1';

        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findEscalas(): array
    {
        $sql = 'SELECT id, nombre, valor_min, valor_max, descripcion, activo, created_at
                FROM escalas_valoracion
                WHERE activo = 1
                ORDER BY nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO periodos (escala_id, nombre, fecha_inicio, fecha_fin, activo, cerrado)
                VALUES (:escala_id, :nombre, :fecha_inicio, :fecha_fin, :activo, :cerrado)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'escala_id'    => (int) $data['escala_id'],
            'nombre'       => trim($data['nombre']),
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin'    => $data['fecha_fin'],
            'activo'       => isset($data['activo']) ? (int) $data['activo'] : 1,
            'cerrado'      => isset($data['cerrado']) ? (int) $data['cerrado'] : 0
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE periodos
                SET escala_id = :escala_id, nombre = :nombre, fecha_inicio = :fecha_inicio, 
                    fecha_fin = :fecha_fin, activo = :activo, cerrado = :cerrado
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'           => $id,
            'escala_id'    => (int) $data['escala_id'],
            'nombre'       => trim($data['nombre']),
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin'    => $data['fecha_fin'],
            'activo'       => isset($data['activo']) ? (int) $data['activo'] : 1,
            'cerrado'      => isset($data['cerrado']) ? (int) $data['cerrado'] : 0
        ]);
    }
}
