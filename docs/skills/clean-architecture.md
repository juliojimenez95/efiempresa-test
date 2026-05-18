# Clean Architecture — Gestor de Competencias

> **Scope:** PHP nativo (POO) · MySQL · ERP Módulo de Competencias  
> **Principio rector:** Separación estricta de capas. La lógica de negocio nunca toca la base de datos directamente. La base de datos nunca dicta la lógica de negocio.

---

## 1. Capas del Sistema

```
public/            ← Punto de entrada HTTP (index.php, vistas .php/.html)
src/
  Controllers/     ← Capa de presentación: recibe Request, devuelve Response
  Services/        ← Capa de aplicación: orquesta casos de uso (gaps, ajustes)
  Repositories/    ← Capa de infraestructura: queries PDO, mapeo a entidades
  Core/            ← Infraestructura transversal: DB, Router, helpers
config/            ← Parámetros de entorno (no lógica)
```

### Regla de dependencia (inviolable)

```
Controllers → Services → Repositories → DB
```

- Un **Controller** NUNCA hace queries directas a la base de datos.
- Un **Repository** NUNCA contiene lógica de negocio (cálculos de gap, % ajuste).
- Un **Service** NUNCA depende de `$_POST`, `$_GET` ni de la capa HTTP.
- El **Core** expone infraestructura, no lógica de dominio.

---

## 2. Capa Controllers

**Responsabilidad única:** Recibir la petición HTTP, delegar al Service correspondiente, devolver la respuesta (JSON o vista renderizada).

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\EvaluacionService;

class EvaluacionController
{
    public function __construct(
        private readonly EvaluacionService $evaluacionService
    ) {}

    public function store(array $requestData): void
    {
        $resultado = $this->evaluacionService->registrarEvaluacion($requestData);
        header('Content-Type: application/json');
        echo json_encode($resultado);
    }
}
```

**Reglas:**
- Máximo 20 líneas por método.
- Sin `if` de lógica de negocio (sólo validación de entrada superficial).
- Siempre inyectar dependencias por constructor (no `new Service()` dentro).
- Responder siempre con `Content-Type: application/json` en endpoints AJAX.

---

## 3. Capa Services

**Responsabilidad única:** Orquestar la lógica de negocio. Aquí viven los cálculos de **gap** y **% de ajuste global**.

### 3.1 Cálculo de Gap y % de Ajuste

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EvaluacionRepository;
use App\Repositories\PerfilObjetivoRepository;

class EvaluacionService
{
    public function __construct(
        private readonly EvaluacionRepository $evaluacionRepository,
        private readonly PerfilObjetivoRepository $perfilObjetivoRepository
    ) {}

    public function registrarEvaluacion(array $data): array
    {
        $this->evaluacionRepository->insertar($data);

        $evaluaciones   = $this->evaluacionRepository->obtenerPorEmpleadoPeriodo(
            (int) $data['empleado_id'],
            (int) $data['periodo_id']
        );
        $perfilObjetivo = $this->perfilObjetivoRepository->obtenerPorPuestoPeriodo(
            (int) $data['puesto_id'],
            (int) $data['periodo_id']
        );

        return $this->calcularResumenAjuste($evaluaciones, $perfilObjetivo);
    }

    private function calcularResumenAjuste(array $evaluaciones, array $perfilObjetivo): array
    {
        $totalPonderado = 0.0;
        $pesoTotal      = 0.0;

        foreach ($evaluaciones as $evaluacion) {
            $competenciaId = $evaluacion['competencia_id'];
            $peso          = (float) ($perfilObjetivo[$competenciaId]['peso'] ?? 0.0);
            $valorIdeal    = (float) ($perfilObjetivo[$competenciaId]['valor_ideal'] ?? 0.0);
            $valorReal     = (float) $evaluacion['puntuacion'];

            $gap             = $valorReal - $valorIdeal;
            $totalPonderado += ($valorIdeal > 0) ? ($valorReal / $valorIdeal) * $peso : 0.0;
            $pesoTotal      += $peso;
        }

        $porcentajeAjuste = ($pesoTotal > 0) ? round(($totalPonderado / $pesoTotal) * 100, 2) : 0.0;

        return [
            'porcentaje_ajuste' => $porcentajeAjuste,
            'evaluaciones'      => $evaluaciones,
        ];
    }
}
```

**Reglas:**
- Ningún Service instancia otro Service directamente (dependen de interfaces o se inyectan).
- Retornar arrays tipados o DTOs simples. Nunca retornar objetos PDOStatement crudos.
- Los métodos privados de cálculo deben tener nombres que expresen intención (`calcularResumenAjuste`, no `calc`).

---

## 4. Capa Repositories

**Responsabilidad única:** Acceso a datos. Queries PDO con Prepared Statements. Mapeo de rows a arrays asociativos o entidades.

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

class EvaluacionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function insertar(array $data): void
    {
        $sql = '
            INSERT INTO evaluaciones
                (empleado_id, competencia_id, periodo_id, puntuacion, semaforo, comentario, created_at)
            VALUES
                (:empleado_id, :competencia_id, :periodo_id, :puntuacion, :semaforo, :comentario, NOW())
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':empleado_id'    => $data['empleado_id'],
            ':competencia_id' => $data['competencia_id'],
            ':periodo_id'     => $data['periodo_id'],
            ':puntuacion'     => $data['puntuacion'],
            ':semaforo'       => $data['semaforo'],
            ':comentario'     => $data['comentario'] ?? null,
        ]);
    }

    public function obtenerPorEmpleadoPeriodo(int $empleadoId, int $periodoId): array
    {
        $sql = '
            SELECT e.*, c.nombre AS competencia_nombre, c.peso
            FROM   evaluaciones e
            INNER  JOIN competencias c ON c.id = e.competencia_id
            WHERE  e.empleado_id = :empleado_id
              AND  e.periodo_id  = :periodo_id
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':empleado_id' => $empleadoId, ':periodo_id' => $periodoId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
```

**Reglas:**
- NUNCA usar interpolación de variables en SQL (`"WHERE id = $id"` → **prohibido**).
- Siempre `PDO::FETCH_ASSOC`. Nunca `FETCH_OBJ` ni `FETCH_BOTH`.
- Un Repository por entidad principal (EvaluacionRepository, CompetenciaRepository, etc.).

---

## 5. Capa Core

### 5.1 Patrón Singleton — Database

```php
<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_NAME']
        );

        try {
            $this->connection = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    // Evitar clonación y deserialización
    private function __clone() {}
    public function __wakeup(): void
    {
        throw new \RuntimeException('Cannot unserialize singleton.');
    }
}
```

---

## 6. Patrón Strategy — Cálculo de Semáforo

Las escalas son **configurables**, no hardcodeadas. El semáforo (rojo/amarillo/verde) depende de la escala del periodo activo.

```php
<?php

declare(strict_types=1);

namespace App\Services\Strategies;

interface SemaforoStrategyInterface
{
    public function calcular(float $gap, float $escalaMax): string;
}
```

```php
<?php

declare(strict_types=1);

namespace App\Services\Strategies;

class SemaforoPorcentualStrategy implements SemaforoStrategyInterface
{
    public function calcular(float $gap, float $escalaMax): string
    {
        $porcentaje = ($escalaMax > 0) ? abs($gap) / $escalaMax : 1.0;

        return match (true) {
            $porcentaje <= 0.10 => 'verde',
            $porcentaje <= 0.30 => 'amarillo',
            default             => 'rojo',
        };
    }
}
```

**Uso en Service:**
```php
$estrategia = new SemaforoPorcentualStrategy();
$semaforo   = $estrategia->calcular($gap, $escalaMaxDelPeriodo);
```

> **Regla de extensibilidad:** Si en el futuro se requiere una estrategia distinta (p. ej. por bloques fijos), se añade una nueva implementación de `SemaforoStrategyInterface` sin tocar el código existente. (**Open/Closed Principle**).

---

## 7. Patrón Repository — Interfaz + Implementación

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface EvaluacionRepositoryInterface
{
    public function insertar(array $data): void;
    public function obtenerPorEmpleadoPeriodo(int $empleadoId, int $periodoId): array;
    public function obtenerHistoricoPorEmpleado(int $empleadoId): array;
}
```

La implementación concreta (`EvaluacionRepository`) implementa esta interfaz. El Service depende de la **interfaz**, no de la implementación concreta → **Dependency Inversion Principle**.

---

## 8. Reglas de Escalabilidad Modular

| Regla | Razón |
|---|---|
| No hardcodear escalas (ej. 1-5) | Las escalas son configurables por periodo desde BD |
| No hardcodear semáforos | Umbrales provienen de la estrategia activa, no del código |
| No mezclar periodos en una query sin filtro explícito | Cada evaluación pertenece a UN periodo. Nunca sobreescribir |
| Versionar perfiles objetivo por periodo | El perfil ideal puede cambiar por periodo; guardar histórico |
| Controllers sin lógica | Toda lógica en Services. Controllers sólo orquestan HTTP |
