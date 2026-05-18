-- =============================================================================
-- SCHEMA — Gestor de Competencias ERP
-- Base de datos: vasalto_competencias | MySQL 8.0 · InnoDB · utf8mb4
-- =============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;
SET sql_mode = 'NO_ENGINE_SUBSTITUTION,STRICT_TRANS_TABLES';

USE vasalto_competencias;

-- =============================================================================
-- BLOQUE 1: MAESTROS ERP SIMULADOS (areas, puestos, empleados)
-- =============================================================================

CREATE TABLE IF NOT EXISTS areas (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(120) NOT NULL,
    descripcion TEXT         NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_areas_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Departamentos del ERP (simulado)';


CREATE TABLE IF NOT EXISTS puestos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_id     INT UNSIGNED NOT NULL,
    nombre      VARCHAR(120) NOT NULL,
    descripcion TEXT         NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_puestos_area   (area_id),
    INDEX idx_puestos_activo (activo),
    CONSTRAINT fk_puestos_area FOREIGN KEY (area_id) REFERENCES areas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cargos por área (simulado ERP)';


CREATE TABLE IF NOT EXISTS empleados (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    area_id       INT UNSIGNED NOT NULL,
    puesto_id     INT UNSIGNED NOT NULL,
    nombre        VARCHAR(80)  NOT NULL,
    apellidos     VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL,
    fecha_ingreso DATE         NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_empleados_email   (email),
    INDEX idx_empleados_area        (area_id),
    INDEX idx_empleados_puesto      (puesto_id),
    INDEX idx_empleados_activo      (activo),
    CONSTRAINT fk_empleados_area   FOREIGN KEY (area_id)   REFERENCES areas(id)   ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_empleados_puesto FOREIGN KEY (puesto_id) REFERENCES puestos(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Empleados evaluados (simulado ERP)';


-- =============================================================================
-- BLOQUE 2: CONFIGURACIÓN PARAMETRIZABLE DEL MÓDULO
-- =============================================================================

CREATE TABLE IF NOT EXISTS escalas_valoracion (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(120) NOT NULL COMMENT 'Ej: Escala 1-5, Escala 0-10',
    valor_min   DECIMAL(5,2) NOT NULL,
    valor_max   DECIMAL(5,2) NOT NULL,
    descripcion TEXT         NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_escalas_activo (activo),
    CONSTRAINT chk_escala_rango CHECK (valor_max > valor_min)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Escalas de valoración configurables — nunca hardcodeadas en PHP';


CREATE TABLE IF NOT EXISTS periodos (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    escala_id    INT UNSIGNED NOT NULL,
    nombre       VARCHAR(100) NOT NULL,
    fecha_inicio DATE         NOT NULL,
    fecha_fin    DATE         NOT NULL,
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    cerrado      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1=inmutable, bloquea nuevas evaluaciones',
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_periodos_escala  (escala_id),
    INDEX idx_periodos_activo  (activo),
    INDEX idx_periodos_cerrado (cerrado),
    CONSTRAINT fk_periodos_escala FOREIGN KEY (escala_id) REFERENCES escalas_valoracion(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_periodo_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Periodos de evaluación. cerrado=1 hace los registros inmutables.';


CREATE TABLE IF NOT EXISTS competencias_bloques (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(120) NOT NULL,
    descripcion TEXT         NULL,
    orden       TINYINT      NOT NULL DEFAULT 0,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_bloques_activo (activo),
    INDEX idx_bloques_orden  (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Familias o bloques de competencias';


CREATE TABLE IF NOT EXISTS competencias (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bloque_id   INT UNSIGNED NOT NULL,
    nombre      VARCHAR(150) NOT NULL,
    descripcion TEXT         NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_competencias_bloque (bloque_id),
    INDEX idx_competencias_activo (activo),
    CONSTRAINT fk_competencias_bloque FOREIGN KEY (bloque_id) REFERENCES competencias_bloques(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo maestro de competencias';


-- =============================================================================
-- BLOQUE 3: OPERATIVAS — PERFILES OBJETIVO Y EVALUACIONES
-- =============================================================================

CREATE TABLE IF NOT EXISTS perfiles_objetivo (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    puesto_id      INT UNSIGNED NOT NULL,
    competencia_id INT UNSIGNED NOT NULL,
    periodo_id     INT UNSIGNED NOT NULL,
    valor_ideal    DECIMAL(5,2) NOT NULL,
    peso           DECIMAL(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Peso % en el cálculo del ajuste global (0-100)',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_perfil_puesto_comp_periodo (puesto_id, competencia_id, periodo_id),
    INDEX idx_perfil_puesto      (puesto_id),
    INDEX idx_perfil_competencia (competencia_id),
    INDEX idx_perfil_periodo     (periodo_id),
    CONSTRAINT fk_perfil_puesto      FOREIGN KEY (puesto_id)      REFERENCES puestos(id)      ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_perfil_competencia FOREIGN KEY (competencia_id) REFERENCES competencias(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_perfil_periodo     FOREIGN KEY (periodo_id)     REFERENCES periodos(id)     ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_perfil_peso       CHECK (peso >= 0 AND peso <= 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Perfil objetivo versionado por puesto/competencia/periodo. UNIQUE garantiza no sobreescritura.';


-- Cabecera: una por empleado por periodo
CREATE TABLE IF NOT EXISTS evaluaciones_cabecera (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id       INT UNSIGNED NOT NULL,
    periodo_id        INT UNSIGNED NOT NULL,
    puesto_id         INT UNSIGNED NOT NULL COMMENT 'Snapshot del puesto al momento de evaluar',
    porcentaje_ajuste DECIMAL(5,2) NULL     COMMENT 'Calculado y persistido. NULL=aún sin evaluar.',
    evaluado_por      VARCHAR(120) NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_cabecera_empleado_periodo (empleado_id, periodo_id),
    INDEX idx_cabecera_empleado      (empleado_id),
    INDEX idx_cabecera_periodo       (periodo_id),
    INDEX idx_cabecera_puesto        (puesto_id),
    INDEX idx_cabecera_emp_periodo   (empleado_id, periodo_id),  -- Matriz Resumen
    CONSTRAINT fk_cabecera_empleado FOREIGN KEY (empleado_id) REFERENCES empleados(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cabecera_periodo  FOREIGN KEY (periodo_id)  REFERENCES periodos(id)  ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cabecera_puesto   FOREIGN KEY (puesto_id)   REFERENCES puestos(id)  ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cabecera de evaluación por empleado y periodo. Contiene % de ajuste global.';


-- Detalle: una fila por competencia evaluada en la cabecera
CREATE TABLE IF NOT EXISTS evaluaciones_detalle (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cabecera_id    INT UNSIGNED NOT NULL,
    competencia_id INT UNSIGNED NOT NULL,
    puntuacion     DECIMAL(5,2) NOT NULL,
    gap            DECIMAL(6,2) NOT NULL COMMENT 'puntuacion - valor_ideal (puede ser negativo)',
    semaforo       ENUM('verde','amarillo','rojo') NOT NULL,
    comentario     TEXT         NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_detalle_cabecera_competencia (cabecera_id, competencia_id),
    INDEX idx_detalle_cabecera    (cabecera_id),
    INDEX idx_detalle_competencia (competencia_id),
    INDEX idx_detalle_cab_comp    (cabecera_id, competencia_id),  -- JOIN eficiente Matriz
    CONSTRAINT fk_detalle_cabecera    FOREIGN KEY (cabecera_id)    REFERENCES evaluaciones_cabecera(id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detalle_competencia FOREIGN KEY (competencia_id) REFERENCES competencias(id)          ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detalle de evaluación por competencia. Registros inmutables en periodos cerrados.';


SET foreign_key_checks = 1;

-- =============================================================================
-- RESUMEN: 10 tablas creadas
--   ERP simulado:  areas, puestos, empleados
--   Config módulo: escalas_valoracion, periodos, competencias_bloques, competencias
--   Operativas:    perfiles_objetivo, evaluaciones_cabecera, evaluaciones_detalle
-- =============================================================================
