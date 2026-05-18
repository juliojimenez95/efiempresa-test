# Roadmap de Desarrollo — Gestor de Competencias ERP

> **Proyecto:** Módulo "Gestor de Competencias" integrado en ERP  
> **Stack:** PHP 8.2+ nativo (POO) · MySQL 8.0+ · Bootstrap 5 · Fetch API  
> **Última actualización:** 2026-05-18  
> **Estado general:** 🟡 En progreso — Core de Backend iniciado (50%)

---

## Convenciones

| Símbolo | Significado |
|---|---|
| 🟩 | Bloque completado |
| 🟨 | Bloque en progreso |
| ⬜ | Bloque pendiente |
| `[x]` | Actividad completada |
| `[ ]` | Actividad pendiente |
| `[~]` | Actividad en progreso |
| `[!]` | Actividad bloqueada o requiere decisión |

---

## 🟩 Bloque 1: Configuración del Core y Entorno [COMPLETADO]

- [x] **Actividad 1.1:** Inicializar directrices del agente (`agent.md` / `AGENTS.md`) y configurar skills de arquitectura (`docs/skills/`).
  - `docs/skills/clean-architecture.md` — Capas, Singleton, Strategy, Repository
  - `docs/skills/php-mysql-rules.md` — tipado estricto, PDO, índices, histórico
  - `docs/skills/frontend-spec.md` — Bootstrap, CSS semáforos, Fetch API
  - `agent.md` + `AGENTS.md` — marco rector del agente para todo el desarrollo

- [x] **Actividad 1.2:** Decisión de acoplamiento con maestros del ERP tomada.
  > ✅ **Decisión:** `areas`, `puestos` y `empleados` se implementan como tablas locales simuladas del ERP. El módulo es autónomo y portable. En producción se podrían reemplazar por vistas SQL del ERP padre sin cambiar la lógica de negocio.

- [x] **Actividad 1.3 (añadida):** Infraestructura Docker para BD portable.
  - `docker-compose.yml` — MySQL 8.0 con healthcheck, named volume `mysql_data`, init automático vía `/docker-entrypoint-initdb.d/`
  - Mapeo `01_schema.sql` + `02_seed.sql` para inicialización ordenada
  - `.gitignore` actualizado con exclusiones Docker y `vendor/`

---

## 🟩 Bloque 2: Diseño de Persistencia y Base de Datos (MySQL) [COMPLETADO]

- [x] **Actividad 2.1:** `database/schema.sql` creado con 10 tablas:
  - **ERP simulado:** `areas`, `puestos`, `empleados`
  - **Config módulo:** `escalas_valoracion`, `periodos`, `competencias_bloques`, `competencias`
  - **Operativas:** `perfiles_objetivo`, `evaluaciones_cabecera`, `evaluaciones_detalle`
  - `UNIQUE KEY (puesto_id, competencia_id, periodo_id)` en `perfiles_objetivo` — versionado garantizado
  - `cerrado TINYINT(1)` en `periodos` — bloquea INSERTs en periodos históricos
  - `porcentaje_ajuste` persistido en cabecera para performance en Matriz Resumen

- [x] **Actividad 2.2:** Indexación explícita aplicada en todas las tablas:
  - `idx_cabecera_emp_periodo (empleado_id, periodo_id)` — query crítica Matriz Resumen
  - `idx_detalle_cab_comp (cabecera_id, competencia_id)` — JOIN eficiente en vistas
  - `idx_perfil_periodo`, `idx_cabecera_periodo` — filtros por periodo en todas las tablas
  - `ENGINE=InnoDB`, `CHARSET=utf8mb4`, `COLLATE=utf8mb4_unicode_ci` en todas las tablas ✅

- [x] **Actividad 2.3:** `database/seed.sql` creado para departamento piloto Nómina:
  - 1 escala configurable (1-5), 2 periodos (Q1 cerrado + Q2 abierto)
  - 5 competencias en 2 bloques, pesos distintos por competencia
  - 2 puestos (Analista, Coordinador) con perfiles objetivo versionados Q1 y Q2
  - 6 empleados con evaluaciones Q1 completas (55% crítico → 97% excelente) + 2 evaluaciones Q2

---

## 🟨 Bloque 3: Backend e Infraestructura Core (PHP Nativo POO) [EN PROGRESO]

- [x] **Actividad 3.0:** Configuración del entorno de conexión:
  - `config/database.php` — constantes de conexión para el contenedor Docker de MySQL ✅
  - `public/index.php` — punto de entrada HTTP único (front controller) con cargador PSR-4 nativo ✅
  - `.htaccess` o rutas relativas limpias totalmente configuradas ✅

- [x] **Actividad 3.1:** Implementación de `src/Core/Database.php` (Singleton PDO con tipado estricto):
  - `PDO::ATTR_ERRMODE = ERRMODE_EXCEPTION` ✅
  - `PDO::ATTR_DEFAULT_FETCH_MODE = FETCH_ASSOC` ✅
  - `PDO::ATTR_EMULATE_PREPARES = false` ✅
  - Método `getInstance(): self` compatible con PHP 7.4 y 8.2+ ✅
  - Prevención de clonación y deserialización implementadas ✅

- [x] **Actividad 3.1b:** Implementación de `src/Core/Router.php`:
  - Mapeo de rutas `GET` / `POST` a Controllers o Closures de prueba ✅
  - Soporte de rutas AJAX diferenciadas y respuestas JSON estructuradas ✅
  - Respuesta `404` asíncrona (JSON) y síncrona (HTML Bootstrap) integrada ✅

- [ ] **Actividad 3.2:** Creación de la capa `src/Repositories/`:
  - Interfaces en `src/Repositories/Contracts/` (`EvaluacionRepositoryInterface`, etc.)
  - Implementaciones concretas con PDO Prepared Statements
  - Sin lógica de negocio. Sólo queries + mapeo a arrays asociativos
  - Repositories requeridos: `EvaluacionRepository`, `CompetenciaRepository`, `PeriodoRepository`, `PerfilObjetivoRepository`, `EmpleadoRepository`

- [ ] **Actividad 3.3:** Creación de la capa `src/Services/`:
  - `EvaluacionService` — orquesta el guardado, calcula gap y % ajuste global ponderado
  - `SemaforoService` — aplica el patrón Strategy con `SemaforoStrategyInterface`
  - `PerfilObjetivoService` — gestión del perfil ideal por puesto/periodo
  - Regla: los Services reciben arrays o DTOs simples. Nunca objetos HTTP.

- [ ] **Actividad 3.4:** Creación de los `src/Controllers/` para endpoints que retornen JSON estructurado:
  - `EvaluacionController` — POST guardar evaluación → retorna `{ porcentaje_ajuste, evaluaciones[] }`
  - `FichaController` — GET ficha individual del empleado con histórico completo
  - `MatrizController` — GET matriz resumen del equipo (con filtros por periodo/departamento)
  - `CatalogoController` — CRUD competencias, escalas, periodos, bloques

---

## ⬜ Bloque 4: Frontend Dinámico e Interfaz de Usuario (JS / Bootstrap) [PENDIENTE]

- [ ] **Actividad 4.1:** Interfaz de Evaluación Operativa "One-to-One" (`public/views/evaluacion.php`):
  - Formulario con puntuación, comentario y semáforo por competencia
  - `min`/`max` del input inyectados desde el servidor (escala del periodo activo)
  - Fetch API: POST al guardar → refresca semáforos y `badge-ajuste-global` sin recargar
  - IDs semánticos: `semaforo-{id}`, `gap-{id}`, `badge-ajuste-global`
  - Notificaciones Bootstrap en `#notificaciones` (no `alert()` nativo)

- [ ] **Actividad 4.2:** Ficha Individual de Competencias (`public/views/ficha.php`):
  - Renderizado de histórico de evaluaciones por periodo
  - Gráfica o tabla de comparación Ideal vs Actual por competencia
  - Columnas: Competencia · Peso · Valor Ideal · Valor Real · GAP · Semáforo · % Ajuste
  - Filtro por periodo (dropdown dinámico)

- [ ] **Actividad 4.3:** Matriz Resumen por Equipo (`public/views/matriz.php`):
  - Tabla cruzada: filas = empleados, columnas = competencias
  - Semáforos visuales por celda
  - Filtros: por periodo y por departamento/área
  - Alertas visuales para empleados con % ajuste crítico (< umbral configurable)

- [ ] **Actividad 4.4:** Catálogo / Mantenimiento (`public/views/catalogo.php`):
  > ⚠️ **Actividad detectada como faltante en el briefing original.** El briefing exige mantenimiento de competencias, familias/bloques, escalas de valoración y periodos.
  - CRUD de competencias (nombre, bloque, peso, activación)
  - CRUD de bloques/familias de competencias
  - CRUD de escalas de valoración (min, max, descripción)
  - CRUD de periodos (nombre, fechas, escala asignada, cierre)
  - Actualización de UI sin recarga (Fetch API para operaciones CRUD)

- [ ] **Actividad 4.5:** CSS personalizado y sistema visual:
  - `public/css/semaforos.css` — clases `.semaforo--verde/amarillo/rojo`, `.badge-ajuste--alto/medio/bajo`
  - `public/css/evaluacion.css` — estilos pantalla One-to-One
  - `public/css/matriz.css` — estilos tabla resumen
  - `public/css/main.css` — variables CSS globales, tipografía, layout base
  - `public/js/evaluacion.js`, `semaforo.js`, `filtros.js`, `utils.js`

---

## ⬜ Bloque 5: Entregables Finales y Estimación [PENDIENTE]

- [ ] **Actividad 5.1:** Redacción del informe técnico de arquitectura y sustentación (4 a 6 páginas):
  - Justificación de decisiones de arquitectura (Clean Architecture, patrones usados)
  - Diagrama de capas y flujo de datos
  - Decisiones de escalabilidad y configurabilidad
  - Estrategia de trazabilidad e historicidad

- [ ] **Actividad 5.2:** Cuadro de estimación formal de horas por bloque para el roadmap presupuestal de la Fase 1:
  - Tabla de estimación por actividad (horas mínimas / horas máximas)
  - Total de horas del proyecto
  - Observaciones de riesgo y dependencias

---

## Dependencias Críticas Identificadas

```
Bloque 1 → Bloque 2 → Bloque 3 → Bloque 4 → Bloque 5
              ↑
        Actividad 1.2 (acoplamiento con ERP maestro)
        debe resolverse ANTES de cerrar el schema.sql
```

> **Nota de arquitectura:** La Actividad 1.2 (integración con maestros del ERP) debe resolverse antes de finalizar `schema.sql`. Si `empleados` y `puestos` vienen de un sistema externo, las tablas locales serán vistas o referencias. Si son propias, se incluyen en el schema completo. Esta decisión afecta el Bloque 2 y el Bloque 3.

---

## Progreso por Bloque

| Bloque | Actividades | Completadas | % |
|---|---|---|---|
| 1 — Config y Entorno | 3 | 3 | 100% ✅ |
| 2 — Base de Datos | 3 | 3 | 100% ✅ |
| 3 — Backend PHP | 5 | 3 | 60% 🟨 |
| 4 — Frontend | 5 | 0 | 0% |
| 5 — Entregables | 2 | 0 | 0% |
| **Total** | **18** | **9** | **50%** |
