# Agent Configuration — Gestor de Competencias EFI

> **Proyecto:** Módulo "Gestor de Competencias" integrado en ERP  
> **Stack:** PHP 8.2+ nativo (POO) · MySQL 8.0+ · Bootstrap 5 · Fetch API  
> **Versión del agente:** 1.0 · Fecha: 2026-04-10

---

## Propósito

Este archivo define las reglas, habilidades (skills) y restricciones que rigen **todo el desarrollo** de este módulo. Cualquier agente de IA, desarrollador o herramienta de asistencia que trabaje en este repositorio **debe leer y aplicar este documento antes de escribir código**.

---

## Alcance del Sistema

El Gestor de Competencias es un módulo parametrizable de ERP que permite:

1. **Catálogo y Parametrización:** Mantenimiento de competencias, familias/bloques, escalas de valoración configurables (NO hardcodeadas), pesos, semáforos, periodos y activación/desactivación.
2. **Perfiles Objetivo & Evaluación:** Definición de perfil ideal por puesto/área versionado por periodo. Pantalla One-to-One para evaluar empleados (puntuación, semáforo, comentarios) recalculando gaps y % de ajuste global dinámicamente en la UI sin recargar la página.
3. **Vistas:** Ficha individual (histórico, ideal vs actual, gap, % ajuste) y Matriz Resumen por equipo con filtros.
4. **Trazabilidad:** Conservación estricta de históricos por periodos. Nunca sobreescribir registros de periodos pasados.

---

## Skills Activas

Las siguientes habilidades son el marco de referencia estricto para todo el desarrollo. Leer cada archivo antes de implementar cualquier funcionalidad relacionada.

### Skill 1 — Clean Architecture
**Archivo:** [`docs/skills/clean-architecture.md`](./docs/skills/clean-architecture.md)

**Aplica cuando:** Se crea o modifica cualquier archivo en `src/` (Controllers, Services, Repositories, Core).

**Reglas clave:**
- La capa `Controllers` sólo recibe HTTP y delega. Sin lógica de negocio.
- La capa `Services` orquesta la lógica: cálculo de gaps, % de ajuste, semáforos.
- La capa `Repositories` sólo ejecuta queries PDO. Sin cálculos de negocio.
- `Core/Database` implementa el patrón **Singleton** para la conexión PDO.
- El semáforo se calcula mediante el patrón **Strategy** con `SemaforoStrategyInterface`.
- Los Repositories implementan interfaces (patrón **Repository**) para permitir sustitución y testing.
- Flujo de dependencias: `Controllers → Services → Repositories → DB`. Inviolable.

### Skill 2 — PHP & MySQL Rules
**Archivo:** [`docs/skills/php-mysql-rules.md`](./docs/skills/php-mysql-rules.md)

**Aplica cuando:** Se escribe o revisa cualquier archivo `.php` o script SQL.

**Reglas clave:**
- `declare(strict_types=1)` en **todos** los archivos PHP. Sin excepción.
- Tipado completo en parámetros y retornos de todas las funciones/métodos.
- PDO con Prepared Statements **siempre**. Cero interpolación de variables en SQL.
- Configuración PDO: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES = false`.
- Índices en todas las llaves foráneas y columnas de filtro frecuente (`periodo_id`, `empleado_id`).
- Las **escalas de valoración** vienen de la tabla `escalas_valoracion` en BD, jamás hardcodeadas.
- Los periodos tienen columna `cerrado TINYINT(1)`. Un periodo cerrado no admite nuevas evaluaciones ni updates.
- El perfil objetivo se versiona por periodo (tabla `perfiles_objetivo` con `UNIQUE KEY` en `puesto_id + competencia_id + periodo_id`).
- Inyección de dependencias por constructor. Nunca `new Dependency()` dentro de métodos.

### Skill 3 — Frontend Spec
**Archivo:** [`docs/skills/frontend-spec.md`](./docs/skills/frontend-spec.md)

**Aplica cuando:** Se crea o modifica cualquier archivo en `public/css/`, `public/js/` o `public/views/`.

**Reglas clave:**
- Fetch API nativa para todas las llamadas AJAX. Sin jQuery ni librerías externas adicionales.
- Al guardar una evaluación, el backend retorna JSON con `porcentaje_ajuste` y array `evaluaciones`. El JS refresca los semáforos y el badge de ajuste **sin recargar la página**.
- Los semáforos usan clases CSS (`semaforo--verde`, `semaforo--amarillo`, `semaforo--rojo`). Nunca colores inline.
- Los IDs del DOM son semánticos y predecibles: `semaforo-{competencia_id}`, `gap-{competencia_id}`, `badge-ajuste-global`.
- Los atributos `min` y `max` del input de puntuación se inyectan desde el servidor (escala del periodo). No hardcodeados.
- `alert()` nativo está prohibido. Usar el sistema de notificaciones Bootstrap (`#notificaciones`).
- `htmlspecialchars()` en todas las variables PHP renderizadas en HTML.
- Todos los `<script>` con atributo `defer`.

### Skill 4 — Git & GitHub Rules
**Archivo:** [`docs/skills/github-git-rules.md`](./docs/skills/github-git-rules.md)

**Aplica cuando:** Se realizan confirmaciones (commits), creación de ramas, fusiones o revisión del historial.

**Reglas clave:**
- **Conventional Commits:** Prefijos estructurados (`feat:`, `fix:`, `chore:`, `refactor:`, `docs:`, `style:`, `perf:`, `test:`, `build:`, `ci:`, `revert:`).
- **Atomicidad:** Cada commit contiene únicamente un cambio lógico único e independiente.
- **Ramas claras:** Nombres de rama con prefijo de tipo (`feat/one-to-one`, `fix/db-connection`).
- **Estado compilable:** Cada commit individual debe dejar el repositorio en un estado funcional y compilable.

---

## Restricciones de Modularidad (No Negociables)

Estas restricciones surgen directamente del briefing técnico y no pueden omitirse ni postergarse:

| Restricción | Descripción |
|---|---|
| **Escalas configurables** | La escala de valoración (mínimo, máximo, intervalos) se gestiona en BD, tabla `escalas_valoracion`. Ningún valor de escala aparece hardcodeado en PHP o JS. |
| **Periodos históricos intactos** | Nunca `UPDATE` en evaluaciones de periodos anteriores. Los registros son inmutables. El cierre de un periodo (`cerrado = 1`) bloquea nuevos registros. |
| **Sin código embebido en vistas** | Los archivos en `public/views/` sólo contienen HTML + referencias a CSS/JS. La lógica de negocio (cálculos, queries) vive en `src/Services/` y `src/Repositories/`. |
| **Semáforos configurables** | Los umbrales del semáforo se calculan mediante el patrón Strategy. Se puede cambiar la estrategia sin tocar código de Controllers ni vistas. |
| **Perfiles versionados** | El perfil ideal de un puesto puede cambiar de un periodo a otro. Cada versión queda registrada. Nunca sobreescribir el perfil de un periodo pasado. |
| **Pesos por competencia** | El peso de cada competencia en el cálculo del % de ajuste global se configura en la tabla `perfiles_objetivo`, no en código PHP. |
| **UI sin recarga** | La pantalla de evaluación One-to-One actualiza gaps y % de ajuste vía Fetch API. No usar `form action` con recarga tradicional. |

---

## Estructura de Carpetas del Proyecto

```
efiempresa-test/
├── config/                   ← Configuración de entorno (DB, rutas)
├── docs/
│   └── skills/
│       ├── clean-architecture.md
│       ├── php-mysql-rules.md
│       ├── frontend-spec.md
│       └── github-git-rules.md
├── public/
│   ├── css/
│   │   ├── main.css
│   │   ├── semaforos.css
│   │   ├── evaluacion.css
│   │   └── matriz.css
│   ├── js/
│   │   ├── evaluacion.js
│   │   ├── semaforo.js
│   │   ├── filtros.js
│   │   └── utils.js
│   ├── views/
│   │   ├── evaluacion.php    ← Pantalla One-to-One
│   │   ├── ficha.php         ← Ficha individual con histórico
│   │   ├── matriz.php        ← Matriz resumen por equipo
│   │   └── catalogo.php      ← CRUD de competencias, escalas, periodos
│   └── index.php             ← Punto de entrada HTTP / Router
├── src/
│   ├── Controllers/          ← HTTP handlers, sin lógica de negocio
│   ├── Services/             ← Lógica de negocio (gaps, ajustes, semáforos)
│   ├── Repositories/         ← Queries PDO + interfaces de contratos
│   └── Core/                 ← Database (Singleton), Router, helpers
├── agent.md                  ← Este archivo
├── AGENTS.md                 ← Duplicado de agent.md (para herramientas alternativas)
└── .gitignore
```

---

## Comportamiento del Agente

Todo agente o asistente de IA que opere en este repositorio debe:

1. **Leer este archivo primero** antes de proponer o generar código.
2. **Leer el skill correspondiente** según el archivo que va a modificar (ver tabla de skills arriba).
3. **Verificar la restricción de modularidad** aplicable antes de implementar.
4. **No generar código** que viole cualquiera de las reglas definidas en los tres skills.
5. **Proponer arquitectura antes que código.** Si una tarea requiere nueva estructura de capas o tablas, proponer el diseño primero.
6. **Usar Prepared Statements siempre.** No hay excepción para queries SQL.
7. **No hardcodear escalas, semáforos ni periodos.** Toda configuración viene de BD.
8. **Preservar históricos.** Nunca proponer `UPDATE` sobre evaluaciones de periodos cerrados.
