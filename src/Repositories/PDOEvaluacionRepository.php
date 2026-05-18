<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\EvaluacionRepositoryInterface;
use PDO;

/**
 * Implementación del repositorio de Evaluaciones utilizando PDO.
 * Administra las transacciones y persistencia para cabeceras y detalles de evaluaciones.
 */
class PDOEvaluacionRepository implements EvaluacionRepositoryInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * {@inheritdoc}
     */
    public function findCabecera(int $empleadoId, int $periodoId): ?array
    {
        $sql = 'SELECT id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por, created_at, updated_at
                FROM evaluaciones_cabecera
                WHERE empleado_id = :empleado_id AND periodo_id = :periodo_id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'empleado_id' => $empleadoId,
            'periodo_id'  => $periodoId
        ]);

        $result = $stmt->fetch();
        return $result ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function findDetalle(int $cabeceraId): array
    {
        $sql = 'SELECT ed.id, ed.cabecera_id, ed.competencia_id, ed.puntuacion, ed.gap, ed.semaforo, ed.comentario, ed.created_at,
                       c.nombre AS competencia_nombre, c.descripcion AS competencia_descripcion,
                       cb.nombre AS bloque_nombre, cb.orden AS bloque_orden,
                       po.valor_ideal, po.peso
                FROM evaluaciones_detalle ed
                INNER JOIN evaluaciones_cabecera ec ON ed.cabecera_id = ec.id
                INNER JOIN competencias c ON ed.competencia_id = c.id
                INNER JOIN competencias_bloques cb ON c.bloque_id = cb.id
                LEFT JOIN perfiles_objetivo po ON ec.puesto_id = po.puesto_id 
                                             AND ed.competencia_id = po.competencia_id 
                                             AND ec.periodo_id = po.periodo_id
                WHERE ed.cabecera_id = :cabecera_id
                ORDER BY cb.orden ASC, c.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['cabecera_id' => $cabeceraId]);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function saveCabecera(array $data): int
    {
        $existing = $this->findCabecera((int) $data['empleado_id'], (int) $data['periodo_id']);

        if ($existing) {
            $sql = 'UPDATE evaluaciones_cabecera
                    SET puesto_id = :puesto_id, porcentaje_ajuste = :porcentaje_ajuste, evaluado_por = :evaluado_por
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'id'                => (int) $existing['id'],
                'puesto_id'         => (int) $data['puesto_id'],
                'porcentaje_ajuste' => isset($data['porcentaje_ajuste']) ? (float) $data['porcentaje_ajuste'] : null,
                'evaluado_por'      => isset($data['evaluado_por']) ? trim($data['evaluado_por']) : null
            ]);

            return (int) $existing['id'];
        }

        $sql = 'INSERT INTO evaluaciones_cabecera (empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por)
                VALUES (:empleado_id, :periodo_id, :puesto_id, :porcentaje_ajuste, :evaluado_por)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'empleado_id'       => (int) $data['empleado_id'],
            'periodo_id'        => (int) $data['periodo_id'],
            'puesto_id'         => (int) $data['puesto_id'],
            'porcentaje_ajuste' => isset($data['porcentaje_ajuste']) ? (float) $data['porcentaje_ajuste'] : null,
            'evaluado_por'      => isset($data['evaluado_por']) ? trim($data['evaluado_por']) : null
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function saveDetalle(array $data): bool
    {
        $sqlSelect = 'SELECT id FROM evaluaciones_detalle 
                      WHERE cabecera_id = :cabecera_id AND competencia_id = :competencia_id';
        
        $stmtSelect = $this->pdo->prepare($sqlSelect);
        $stmtSelect->execute([
            'cabecera_id'    => (int) $data['cabecera_id'],
            'competencia_id' => (int) $data['competencia_id']
        ]);
        
        $existingId = $stmtSelect->fetchColumn();

        if ($existingId) {
            $sql = 'UPDATE evaluaciones_detalle
                    SET puntuacion = :puntuacion, gap = :gap, semaforo = :semaforo, comentario = :comentario
                    WHERE id = :id';

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'id'         => (int) $existingId,
                'puntuacion' => (float) $data['puntuacion'],
                'gap'        => (float) $data['gap'],
                'semaforo'   => trim($data['semaforo']),
                'comentario' => isset($data['comentario']) ? trim($data['comentario']) : null
            ]);
        }

        $sql = 'INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario)
                VALUES (:cabecera_id, :competencia_id, :puntuacion, :gap, :semaforo, :comentario)';

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'cabecera_id'    => (int) $data['cabecera_id'],
            'competencia_id' => (int) $data['competencia_id'],
            'puntuacion'     => (float) $data['puntuacion'],
            'gap'            => (float) $data['gap'],
            'semaforo'       => trim($data['semaforo']),
            'comentario'     => isset($data['comentario']) ? trim($data['comentario']) : null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findHistorialByEmpleado(int $empleadoId): array
    {
        $sql = 'SELECT ec.id AS cabecera_id, ec.periodo_id, ec.porcentaje_ajuste, ec.evaluado_por, ec.created_at,
                       p.nombre AS periodo_nombre, p.fecha_inicio, p.fecha_fin, p.cerrado,
                       pu.nombre AS puesto_nombre
                FROM evaluaciones_cabecera ec
                INNER JOIN periodos p ON ec.periodo_id = p.id
                INNER JOIN puestos pu ON ec.puesto_id = pu.id
                WHERE ec.empleado_id = :empleado_id
                ORDER BY p.fecha_inicio DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['empleado_id' => $empleadoId]);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findMatrizResumen(int $periodoId, ?int $areaId = null): array
    {
        $sql = 'SELECT e.id AS empleado_id, e.nombre, e.apellidos, e.email,
                       pu.nombre AS puesto_nombre,
                       a.nombre AS area_nombre, a.id AS area_id,
                       ec.id AS cabecera_id, ec.porcentaje_ajuste, ec.evaluado_por, ec.updated_at
                FROM empleados e
                INNER JOIN puestos pu ON e.puesto_id = pu.id
                INNER JOIN areas a ON e.area_id = a.id
                LEFT JOIN evaluaciones_cabecera ec ON e.id = ec.empleado_id AND ec.periodo_id = :periodo_id
                WHERE e.activo = 1';

        $params = ['periodo_id' => $periodoId];

        if ($areaId !== null) {
            $sql .= ' AND e.area_id = :area_id';
            $params['area_id'] = $areaId;
        }

        $sql .= ' ORDER BY e.apellidos ASC, e.nombre ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function findMatrizDetalles(int $periodoId, ?int $areaId = null): array
    {
        $sql = 'SELECT ed.competencia_id, ed.puntuacion, ed.semaforo, ec.empleado_id
                FROM evaluaciones_detalle ed
                INNER JOIN evaluaciones_cabecera ec ON ed.cabecera_id = ec.id
                INNER JOIN empleados e ON ec.empleado_id = e.id
                WHERE ec.periodo_id = :periodo_id AND e.activo = 1';

        $params = ['periodo_id' => $periodoId];

        if ($areaId !== null) {
            $sql .= ' AND e.area_id = :area_id';
            $params['area_id'] = $areaId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
