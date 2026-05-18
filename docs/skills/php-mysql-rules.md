# PHP & MySQL Rules — Gestor de Competencias

> **Stack:** PHP 8.2+ nativo (POO) · MySQL 8.0+  
> **Principio rector:** Código limpio, tipado estricto, datos seguros, histórico indestructible.

---

## 1. Clean Code PHP

### 1.1 `declare(strict_types=1)` en TODOS los archivos

```php
<?php
declare(strict_types=1);
```

Sin esta declaración, el archivo **no se acepta**. Primera línea siempre.

### 1.2 Tipado completo — parámetros y retornos

```php
// ❌ PROHIBIDO
function calcularGap($valorReal, $valorIdeal) { ... }

// ✅ CORRECTO
function calcularGap(float $valorReal, float $valorIdeal): float
{
    return $valorReal - $valorIdeal;
}
```

### 1.3 Naming Conventions

| Elemento | Convención | Ejemplo |
|---|---|---|
| Variables / métodos | `camelCase` | `$porcentajeAjuste`, `calcularGap()` |
| Clases | `PascalCase` | `EvaluacionService` |
| Constantes | `UPPER_SNAKE_CASE` | `MAX_ESCALA_VALORACION` |
| Tablas BD | `snake_case` plural | `evaluaciones`, `perfiles_objetivo` |
| Columnas BD | `snake_case` | `empleado_id`, `periodo_id` |

### 1.4 Guard Clauses (Bouncer Pattern)

```php
// ❌ PROHIBIDO — nesting hadouken
public function registrar(array $data): void
{
    if (!empty($data['empleado_id'])) {
        if ($data['puntuacion'] >= 0) {
            // lógica...
        }
    }
}

// ✅ CORRECTO — retornos tempranos
public function registrar(array $data): void
{
    if (empty($data['empleado_id'])) {
        throw new \InvalidArgumentException('empleado_id es obligatorio.');
    }
    if ($data['puntuacion'] < 0) {
        throw new \InvalidArgumentException('Puntuación no puede ser negativa.');
    }
    // happy path...
}
```

### 1.5 Inyección de Dependencias — Sin `new` en métodos

```php
// ❌ PROHIBIDO
class EvaluacionService {
    public function registrar(array $data): void {
        $repo = new EvaluacionRepository(); // acoplamiento duro
    }
}

// ✅ CORRECTO
class EvaluacionService {
    public function __construct(
        private readonly EvaluacionRepository $evaluacionRepository
    ) {}
}
```

---

## 2. PDO — Prepared Statements (Obligatorio)

### 2.1 Regla absoluta: cero interpolación de variables en SQL

```php
// ❌ PROHIBIDO — SQL Injection inmediata
$sql = "SELECT * FROM evaluaciones WHERE empleado_id = $empleadoId";

// ✅ ÚNICO MÉTODO VÁLIDO
$sql  = 'SELECT * FROM evaluaciones WHERE empleado_id = :empleado_id AND periodo_id = :periodo_id';
$stmt = $pdo->prepare($sql);
$stmt->execute([':empleado_id' => $empleadoId, ':periodo_id' => $periodoId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

### 2.2 Configuración PDO obligatoria

```php
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);
```

### 2.3 Manejo de errores en queries

```php
try {
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($params);
} catch (\PDOException $e) {
    error_log(sprintf('[%s] Error: %s', __CLASS__, $e->getMessage()));
    throw new \RuntimeException('Error al persistir la evaluación.');
}
```

---

## 3. MySQL — Índices y Diseño

### 3.1 Índices obligatorios en llaves foráneas y columnas de filtro

```sql
CREATE TABLE evaluaciones (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id    INT UNSIGNED NOT NULL,
    competencia_id INT UNSIGNED NOT NULL,
    periodo_id     INT UNSIGNED NOT NULL,
    puntuacion     DECIMAL(5,2) NOT NULL,
    semaforo       ENUM('verde','amarillo','rojo') NOT NULL,
    comentario     TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_evaluaciones_empleado    (empleado_id),
    INDEX idx_evaluaciones_periodo     (periodo_id),
    INDEX idx_evaluaciones_competencia (competencia_id),
    INDEX idx_evaluaciones_emp_periodo (empleado_id, periodo_id),

    CONSTRAINT fk_eval_empleado    FOREIGN KEY (empleado_id)    REFERENCES empleados(id),
    CONSTRAINT fk_eval_competencia FOREIGN KEY (competencia_id) REFERENCES competencias(id),
    CONSTRAINT fk_eval_periodo     FOREIGN KEY (periodo_id)     REFERENCES periodos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3.2 Escala configurable — No hardcoded

```sql
-- Escala viene de BD, no de constantes PHP
CREATE TABLE escalas_valoracion (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(100) NOT NULL,
    valor_min   DECIMAL(5,2) NOT NULL,
    valor_max   DECIMAL(5,2) NOT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Estrategia de Histórico por Periodos — Sin Sobreescritura

### 4.1 Regla fundamental

> **Nunca se hace `UPDATE` a una evaluación de un periodo cerrado.**  
> Cada evaluación es un registro inmutable con `created_at`.

### 4.2 Diseño de periodos con bloqueo

```sql
CREATE TABLE periodos (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(100) NOT NULL,
    fecha_inicio DATE         NOT NULL,
    fecha_fin    DATE         NOT NULL,
    escala_id    INT UNSIGNED NOT NULL,
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    cerrado      TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = bloqueado para nuevas evaluaciones
    PRIMARY KEY (id),
    INDEX idx_periodos_activo  (activo),
    INDEX idx_periodos_cerrado (cerrado),
    CONSTRAINT fk_periodo_escala FOREIGN KEY (escala_id) REFERENCES escalas_valoracion(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3 Validación en PHP antes de insertar

```php
public function validarPeriodoAbierto(int $periodoId): void
{
    $sql  = 'SELECT cerrado FROM periodos WHERE id = :id';
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([':id' => $periodoId]);
    $periodo = $stmt->fetch();

    if (!$periodo || (bool) $periodo['cerrado']) {
        throw new \DomainException('No se pueden registrar evaluaciones en un periodo cerrado.');
    }
}
```

### 4.4 Versionado de perfil objetivo por periodo

```sql
CREATE TABLE perfiles_objetivo (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    puesto_id       INT UNSIGNED NOT NULL,
    competencia_id  INT UNSIGNED NOT NULL,
    periodo_id      INT UNSIGNED NOT NULL,
    valor_ideal     DECIMAL(5,2) NOT NULL,
    peso            DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    PRIMARY KEY (id),
    UNIQUE KEY uk_perfil_puesto_comp_periodo (puesto_id, competencia_id, periodo_id),
    INDEX idx_perfil_puesto  (puesto_id),
    INDEX idx_perfil_periodo (periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Reglas de Modularidad

| Restricción | Implementación |
|---|---|
| Escalas configurables | Tabla `escalas_valoracion`, no constantes PHP |
| Sin código embebido en vistas | Lógica sólo en Services/Controllers, vistas sólo presentan |
| Periodos históricos intactos | `cerrado = 1` bloquea todo INSERT en ese periodo |
| Pesos por periodo | Columna `peso` en `perfiles_objetivo` |
| Activación/desactivación | Columna `activo TINYINT(1)` en `competencias` y `escalas_valoracion` |
