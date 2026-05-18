<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\PerfilObjetivoRepositoryInterface;
use PDO;

/**
 * Implementación del repositorio de Perfiles Objetivo utilizando PDO.
 */
class PDOPerfilObjetivoRepository implements PerfilObjetivoRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findByPuestoAndPeriodo(int $puestoId, int $periodoId): array
    {
        $sql = 'SELECT po.id, po.puesto_id, po.competencia_id, po.periodo_id, po.valor_ideal, po.peso, po.created_at,
                       c.nombre AS competencia_nombre, c.descripcion AS competencia_descripcion,
                       cb.nombre AS bloque_nombre, cb.orden AS bloque_orden
                FROM perfiles_objetivo po
                INNER JOIN competencias c ON po.competencia_id = c.id
                INNER JOIN competencias_bloques cb ON c.bloque_id = cb.id
                WHERE po.puesto_id = :puesto_id AND po.periodo_id = :periodo_id
                ORDER BY cb.orden ASC, c.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'puesto_id'  => $puestoId,
            'periodo_id' => $periodoId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): int
    {
        $sql = 'INSERT INTO perfiles_objetivo (puesto_id, competencia_id, periodo_id, valor_ideal, peso)
                VALUES (:puesto_id, :competencia_id, :periodo_id, :valor_ideal, :peso)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'puesto_id'      => (int) $data['puesto_id'],
            'competencia_id' => (int) $data['competencia_id'],
            'periodo_id'     => (int) $data['periodo_id'],
            'valor_ideal'    => (float) $data['valor_ideal'],
            'peso'           => isset($data['peso']) ? (float) $data['peso'] : 1.0
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE perfiles_objetivo
                SET valor_ideal = :valor_ideal, peso = :peso
                WHERE id = :id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id'          => $id,
            'valor_ideal' => (float) $data['valor_ideal'],
            'peso'        => (float) $data['peso']
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteCompetencia(int $puestoId, int $competenciaId, int $periodoId): bool
    {
        $sql = 'DELETE FROM perfiles_objetivo
                WHERE puesto_id = :puesto_id AND competencia_id = :competencia_id AND periodo_id = :periodo_id';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'puesto_id'      => $puestoId,
            'competencia_id' => $competenciaId,
            'periodo_id'     => $periodoId
        ]);
    }
}
