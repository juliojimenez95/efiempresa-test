-- =============================================================================
-- SEED — Gestor de Competencias ERP
-- Departamento piloto: Nómina
-- Datos realistas para validar gaps, semáforos y % de ajuste
-- =============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

USE vasalto_competencias;

-- =============================================================================
-- 1. ESCALA DE VALORACIÓN
-- =============================================================================
INSERT INTO escalas_valoracion (id, nombre, valor_min, valor_max, descripcion) VALUES
(1, 'Escala 1-5', 1.00, 5.00, 'Escala estándar de competencias: 1=Inicial, 5=Experto');


-- =============================================================================
-- 2. PERIODOS (1 cerrado + 1 abierto)
-- =============================================================================
INSERT INTO periodos (id, escala_id, nombre, fecha_inicio, fecha_fin, activo, cerrado) VALUES
(1, 1, 'Q1 2025 — Enero a Marzo', '2025-01-01', '2025-03-31', 1, 1),  -- cerrado=1: inmutable
(2, 1, 'Q2 2025 — Abril a Junio', '2025-04-01', '2025-06-30', 1, 0);  -- cerrado=0: activo


-- =============================================================================
-- 3. ÁREA
-- =============================================================================
INSERT INTO areas (id, nombre, descripcion) VALUES
(1, 'Nómina', 'Departamento de gestión de nómina y compensaciones');


-- =============================================================================
-- 4. PUESTOS (dentro del área Nómina)
-- =============================================================================
INSERT INTO puestos (id, area_id, nombre, descripcion) VALUES
(1, 1, 'Analista de Nómina',      'Procesa y liquida nóminas mensuales y quincenales'),
(2, 1, 'Coordinador de Nómina',   'Supervisa el equipo y valida liquidaciones especiales');


-- =============================================================================
-- 5. EMPLEADOS (6 empleados del área Nómina)
-- =============================================================================
INSERT INTO empleados (id, area_id, puesto_id, nombre, apellidos, email, fecha_ingreso) VALUES
(1, 1, 1, 'Valentina', 'Ríos Gómez',       'v.rios@empresa.com',       '2022-03-15'),
(2, 1, 1, 'Camilo',    'Herrera Zapata',   'c.herrera@empresa.com',    '2021-07-01'),
(3, 1, 1, 'Daniela',   'Morales Vargas',   'd.morales@empresa.com',    '2023-01-10'),
(4, 1, 1, 'Sebastián', 'Torres Ruiz',      's.torres@empresa.com',     '2020-11-20'),
(5, 1, 2, 'Luciana',   'Castro Pedraza',   'l.castro@empresa.com',     '2019-05-08'),
(6, 1, 2, 'Andrés',    'Bermúdez López',   'a.bermudez@empresa.com',   '2018-02-14');


-- =============================================================================
-- 6. BLOQUES DE COMPETENCIAS
-- =============================================================================
INSERT INTO competencias_bloques (id, nombre, descripcion, orden) VALUES
(1, 'Habilidades Técnicas', 'Conocimiento y dominio técnico del rol',   1),
(2, 'Habilidades Blandas',  'Competencias interpersonales y de gestión', 2);


-- =============================================================================
-- 7. COMPETENCIAS (5 competencias activas)
-- =============================================================================
INSERT INTO competencias (id, bloque_id, nombre, descripcion) VALUES
(1, 1, 'Gestión de Nómina',       'Dominio de procesos de liquidación, PILA, parafiscales y novedades'),
(2, 1, 'Legislación Laboral',     'Conocimiento del CST, decretos y normativa vigente de nómina'),
(3, 1, 'Manejo de Herramientas',  'Uso de software especializado de nómina y hojas de cálculo avanzadas'),
(4, 2, 'Atención al Detalle',     'Precisión y rigor en la verificación de cálculos y registros'),
(5, 2, 'Trabajo en Equipo',       'Colaboración efectiva y comunicación asertiva con el equipo');


-- =============================================================================
-- 8. PERFILES OBJETIVO — Analista de Nómina (puesto_id=1)
-- Versionado por periodo: perfil Q1 y perfil Q2 (ligeramente distinto)
-- =============================================================================

-- Perfil Q1 2025 — Analista (periodo_id=1)
INSERT INTO perfiles_objetivo (puesto_id, competencia_id, periodo_id, valor_ideal, peso) VALUES
(1, 1, 1, 4.00, 30.00),  -- Gestión Nómina: ideal 4.0, peso 30%
(1, 2, 1, 3.50, 25.00),  -- Legislación:    ideal 3.5, peso 25%
(1, 3, 1, 3.50, 20.00),  -- Herramientas:   ideal 3.5, peso 20%
(1, 4, 1, 4.00, 15.00),  -- Atención Det.:  ideal 4.0, peso 15%
(1, 5, 1, 3.00, 10.00);  -- Trabajo Equipo: ideal 3.0, peso 10%

-- Perfil Q2 2025 — Analista (periodo_id=2) — se eleva el nivel esperado
INSERT INTO perfiles_objetivo (puesto_id, competencia_id, periodo_id, valor_ideal, peso) VALUES
(1, 1, 2, 4.50, 30.00),
(1, 2, 2, 4.00, 25.00),
(1, 3, 2, 4.00, 20.00),
(1, 4, 2, 4.00, 15.00),
(1, 5, 2, 3.50, 10.00);

-- Perfil Q1 2025 — Coordinador (puesto_id=2) (periodo_id=1)
INSERT INTO perfiles_objetivo (puesto_id, competencia_id, periodo_id, valor_ideal, peso) VALUES
(2, 1, 1, 5.00, 25.00),
(2, 2, 1, 4.50, 25.00),
(2, 3, 1, 4.00, 15.00),
(2, 4, 1, 4.50, 20.00),
(2, 5, 1, 4.50, 15.00);

-- Perfil Q2 2025 — Coordinador (puesto_id=2) (periodo_id=2)
INSERT INTO perfiles_objetivo (puesto_id, competencia_id, periodo_id, valor_ideal, peso) VALUES
(2, 1, 2, 5.00, 25.00),
(2, 2, 2, 4.50, 25.00),
(2, 3, 2, 4.00, 15.00),
(2, 4, 2, 4.50, 20.00),
(2, 5, 2, 4.50, 15.00);


-- =============================================================================
-- 9. EVALUACIONES — Q1 2025 (periodo cerrado, registros inmutables)
-- % ajuste = Σ(puntuacion/ideal * peso) / Σ(peso) * 100
-- =============================================================================

-- Valentina Ríos — Analista — Q1 2025 — Buen desempeño (≈ 90%)
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(1, 1, 1, 1, 90.33, 'Luciana Castro Pedraza');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(1, 1, 3.80, -0.20, 'verde',    'Muy buen manejo del proceso de nómina'),
(1, 2, 3.30, -0.20, 'verde',    'Conocimiento sólido de la norma'),
(1, 3, 3.20, -0.30, 'verde',    'Maneja bien el software actual'),
(1, 4, 3.80, -0.20, 'verde',    'Alta precisión en sus entregas'),
(1, 5, 2.80, -0.20, 'verde',    'Buena dinámica de trabajo');

-- Camilo Herrera — Analista — Q1 2025 — Desempeño medio (≈ 72%)
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(2, 2, 1, 1, 71.80, 'Luciana Castro Pedraza');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(2, 1, 2.80, -1.20, 'amarillo', 'Requiere refuerzo en novedades complejas'),
(2, 2, 2.50, -1.00, 'amarillo', 'Conocimiento básico de la norma laboral'),
(2, 3, 3.00, -0.50, 'verde',    'Manejo aceptable de herramientas'),
(2, 4, 2.90, -1.10, 'amarillo', 'Algunos errores en revisión de planillas'),
(2, 5, 2.50, -0.50, 'verde',    'Trabajo colaborativo aceptable');

-- Daniela Morales — Analista — Q1 2025 — Desempeño crítico (≈ 55%)
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(3, 3, 1, 1, 55.40, 'Luciana Castro Pedraza');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(3, 1, 2.00, -2.00, 'rojo',     'Errores frecuentes en la liquidación de horas extras'),
(3, 2, 1.80, -1.70, 'rojo',     'Desconoce novedades de la reforma tributaria'),
(3, 3, 2.20, -1.30, 'rojo',     'Solo maneja funciones básicas del software'),
(3, 4, 2.50, -1.50, 'rojo',     'Alta tasa de reprocesos'),
(3, 5, 2.00, -1.00, 'amarillo', 'Dificultades para trabajar bajo presión');

-- Sebastián Torres — Analista — Q1 2025 — Buen desempeño (≈ 85%)
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(4, 4, 1, 1, 84.67, 'Luciana Castro Pedraza');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(4, 1, 3.50, -0.50, 'verde',    'Domina bien el ciclo completo de nómina'),
(4, 2, 3.20, -0.30, 'verde',    'Actualizado en normativa 2024-2025'),
(4, 3, 3.00, -0.50, 'verde',    'Buen manejo de Excel avanzado'),
(4, 4, 3.40, -0.60, 'verde',    'Trabajo ordenado y preciso'),
(4, 5, 2.70, -0.30, 'verde',    'Buen integrante del equipo');

-- Luciana Castro — Coordinadora — Q1 2025 — Excelente (≈ 95%)
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(5, 5, 1, 2, 95.11, 'Andrés Bermúdez López');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(5, 1, 4.90, -0.10, 'verde',    'Líder técnica del proceso de nómina'),
(5, 2, 4.40, -0.10, 'verde',    'Experta en legislación laboral colombiana'),
(5, 3, 4.00,  0.00, 'verde',    'Domina todas las herramientas del área'),
(5, 4, 4.30, -0.20, 'verde',    'Cero reprocesos en su gestión'),
(5, 5, 4.30, -0.20, 'verde',    'Excelente liderazgo del equipo');

-- Andrés Bermúdez — Coordinador — Q1 2025 — Bueno (≈ 82%)
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(6, 6, 1, 2, 82.22, 'Luciana Castro Pedraza');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(6, 1, 4.20, -0.80, 'amarillo', 'Sólido en nómina, delega bien en el equipo'),
(6, 2, 3.80, -0.70, 'amarillo', 'Conocimiento amplio, requiere actualización en reforma'),
(6, 3, 3.50, -0.50, 'verde',    'Manejo competente de herramientas'),
(6, 4, 3.80, -0.70, 'verde',    'Revisiones oportunas y precisas'),
(6, 5, 3.90, -0.60, 'verde',    'Buen comunicador y articulador del equipo');


-- =============================================================================
-- 10. EVALUACIONES — Q2 2025 (periodo abierto — evaluaciones parciales)
-- Solo Valentina y Luciana evaluadas hasta ahora
-- =============================================================================

-- Valentina Ríos — Q2 2025
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(7, 1, 2, 1, 83.56, 'Luciana Castro Pedraza');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(7, 1, 4.00, -0.50, 'verde',    'Mejora notable. Maneja bien novedades complejas ahora'),
(7, 2, 3.50, -0.50, 'verde',    'Ha avanzado en legislación'),
(7, 3, 3.80, -0.20, 'verde',    'Domina bien el software tras capacitación Q1'),
(7, 4, 3.70, -0.30, 'verde',    'Mantiene alta precisión'),
(7, 5, 3.30, -0.20, 'verde',    'Mejor integración con el equipo');

-- Luciana Castro — Q2 2025
INSERT INTO evaluaciones_cabecera (id, empleado_id, periodo_id, puesto_id, porcentaje_ajuste, evaluado_por) VALUES
(8, 5, 2, 2, 97.78, 'Andrés Bermúdez López');

INSERT INTO evaluaciones_detalle (cabecera_id, competencia_id, puntuacion, gap, semaforo, comentario) VALUES
(8, 1, 5.00,  0.00, 'verde',    'Nivel experto confirmado'),
(8, 2, 4.50,  0.00, 'verde',    'Referente técnica en legislación laboral'),
(8, 3, 4.00,  0.00, 'verde',    'Dominio total de herramientas'),
(8, 4, 4.40, -0.10, 'verde',    'Cero errores en Q2'),
(8, 5, 4.40, -0.10, 'verde',    'Excelente liderazgo y comunicación');


SET foreign_key_checks = 1;

-- =============================================================================
-- RESUMEN DEL SEED
-- · 1 área (Nómina)
-- · 2 puestos (Analista, Coordinador)
-- · 6 empleados
-- · 1 escala de valoración configurable (1-5)
-- · 2 periodos (Q1 cerrado, Q2 abierto)
-- · 2 bloques, 5 competencias
-- · Perfiles objetivo versionados para ambos puestos y ambos periodos
-- · 6 evaluaciones Q1 (cerradas) + 2 evaluaciones Q2 (abiertas)
-- · Rango de % ajuste: 55% (crítico) a 97% (excelente)
-- =============================================================================
