<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\CompetenciaRepositoryInterface;
use PDO;

/**
 * Implementación concreta del repositorio de Competencias utilizando PDO y SQL nativo.
 */
class PDOCompetenciaRepository implements CompetenciaRepositoryInterface
{
    private PDO $pdo;

    /**
     * Inyección de dependencia de la conexión PDO.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        $sql = 'SELECT c.id, c.bloque_id, c.nombre, c.descripcion, c.activo, c.created_at, 
                       cb.nombre AS bloque_nombre, cb.orden AS bloque_orden
                FROM competencias c
                INNER JOIN competencias_bloques cb ON c.bloque_id = cb.id
                WHERE c.activo = 1 AND cb.activo = 1
                ORDER BY cb.orden ASC, c.nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT c.id, c.bloque_id, c.nombre, c.descripcion, c.activo, c.created_at, 
                       cb.nombre AS bloque_nombre, cb.orden AS bloque_orden
                FROM competencias c
                INNER JOIN competencias_bloques cb ON c.bloque_id = cb.id
                WHERE c.id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findBloques(): array
    {
        $sql = 'SELECT id, nombre, descripcion, orden, activo, created_at
                FROM competencias_bloques
                WHERE activo = 1
                ORDER BY orden ASC, nombre ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO competencias (bloque_id, nombre, descripcion, activo)
                VALUES (:bloque_id, :nombre, :descripcion, :activo)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'bloque_id'   => (int) $data['bloque_id'],
            'nombre'      => trim($data['nombre']),
            'descripcion' => isset($data['descripcion']) ? trim($data['descripcion']) : null,
            'activo'      => isset($data['activo']) ? (int) $data['activo'] : 1
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE competencias
                SET bloque_id = :bloque_id, nombre = :nombre, descripcion = :descripcion, activo = :activo
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'          => $id,
            'bloque_id'   => (int) $data['bloque_id'],
            'nombre'      => trim($data['nombre']),
            'descripcion' => isset($data['descripcion']) ? trim($data['descripcion']) : null,
            'activo'      => isset($data['activo']) ? (int) $data['activo'] : 1
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        // Aplicamos borrado lógico para no romper integridad histórica en evaluaciones pasadas
        $sql = 'UPDATE competencias SET activo = 0 WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
