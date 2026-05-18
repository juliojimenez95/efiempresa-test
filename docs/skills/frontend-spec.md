# Frontend Spec — Gestor de Competencias

> **Stack:** Bootstrap 5 · CSS personalizado · Fetch API (AJAX)  
> **Principio rector:** UI interactiva y asíncrona. Cero recargas de página al guardar evaluaciones. Los GAPs y % de ajuste se actualizan en caliente.

---

## 1. Estructura de Archivos Frontend

```
public/
  css/
    main.css          ← Estilos globales + variables CSS
    semaforos.css     ← Clases específicas para indicadores de semáforo
    evaluacion.css    ← Estilos de la pantalla One-to-One
    matriz.css        ← Estilos de la matriz resumen de equipo
  js/
    evaluacion.js     ← Lógica Fetch API para guardar evaluaciones y refrescar GAP/% ajuste
    semaforo.js       ← Actualización dinámica de semáforos en UI
    filtros.js        ← Lógica de filtros en la vista Matriz Resumen
    utils.js          ← Helpers reutilizables (formateo, debounce, etc.)
  views/
    evaluacion.php    ← Vista One-to-One (HTML + referencias a JS/CSS)
    ficha.php         ← Ficha individual del empleado (histórico + gaps)
    matriz.php        ← Matriz resumen del equipo
    catalogo.php      ← Mantenimiento de competencias, escalas, periodos
```

**Regla:** Ningún archivo de vista `.php` contiene lógica PHP embebida que calcule gaps o ajustes. Las vistas sólo renderizan HTML. La lógica la entrega el backend vía JSON.

---

## 2. Bootstrap 5 — Uso y Restricciones

### 2.1 Uso válido de Bootstrap

- Grid system (`container`, `row`, `col-*`) para layout responsivo.
- Componentes: `card`, `table`, `badge`, `modal`, `form-control`, `btn`.
- Clases utilitarias: `d-flex`, `gap-*`, `mt-*`, `text-center`, etc.

### 2.2 Personalización con CSS propio (NO sobreescribir Bootstrap inline)

```css
/* ❌ PROHIBIDO — sobrescribir Bootstrap con !important inline */
<div style="background-color: red !important;">

/* ✅ CORRECTO — variables CSS y clases semánticas propias */
:root {
    --color-semaforo-verde:    #28a745;
    --color-semaforo-amarillo: #ffc107;
    --color-semaforo-rojo:     #dc3545;
    --color-ajuste-alto:       #1a7f37;
    --color-ajuste-medio:      #bf8700;
    --color-ajuste-bajo:       #cf222e;
    --fuente-principal:        'Inter', sans-serif;
    --radio-tarjeta:           0.5rem;
}
```

---

## 3. CSS Personalizado — Semáforos

Los semáforos son indicadores visuales del gap entre valor real e ideal. Se aplican mediante clases CSS, no colores hardcodeados en HTML.

```css
/* public/css/semaforos.css */

.semaforo {
    display:       inline-block;
    width:         1rem;
    height:        1rem;
    border-radius: 50%;
    margin-right:  0.4rem;
    vertical-align: middle;
}

.semaforo--verde    { background-color: var(--color-semaforo-verde); }
.semaforo--amarillo { background-color: var(--color-semaforo-amarillo); }
.semaforo--rojo     { background-color: var(--color-semaforo-rojo); }

/* Badge de % ajuste en ficha individual */
.badge-ajuste {
    font-size:     0.85rem;
    padding:       0.3em 0.7em;
    border-radius: 0.4rem;
    font-weight:   600;
}
.badge-ajuste--alto   { background-color: var(--color-ajuste-alto);   color: #fff; }
.badge-ajuste--medio  { background-color: var(--color-ajuste-medio);  color: #fff; }
.badge-ajuste--bajo   { background-color: var(--color-ajuste-bajo);   color: #fff; }
```

### 3.1 Actualización dinámica de semáforo en JS

```js
// public/js/semaforo.js

/**
 * Actualiza el indicador visual del semáforo de una competencia en la UI.
 *
 * @param {HTMLElement} elemento - El nodo .semaforo a actualizar
 * @param {string} color - 'verde' | 'amarillo' | 'rojo'
 */
function actualizarSemaforo(elemento, color) {
    elemento.classList.remove('semaforo--verde', 'semaforo--amarillo', 'semaforo--rojo');
    elemento.classList.add(`semaforo--${color}`);
}
```

---

## 4. Fetch API — Guardar Evaluaciones y Actualizar UI en Caliente

### 4.1 Flujo completo: guardar → recalcular → refrescar UI

```js
// public/js/evaluacion.js

'use strict';

const ENDPOINT_EVALUACION = '/api/evaluaciones';

/**
 * Envía la puntuación de una competencia y refresca el resumen de gaps/ajuste
 * sin recargar la página.
 *
 * @param {Event} event
 */
async function guardarEvaluacion(event) {
    event.preventDefault();

    const form       = event.target;
    const submitBtn  = form.querySelector('[type="submit"]');
    const payload    = construirPayload(form);

    submitBtn.disabled = true;

    try {
        const resultado = await enviarEvaluacion(payload);
        refrescarResumenAjuste(resultado);
        mostrarNotificacion('Evaluación guardada correctamente.', 'success');
    } catch (error) {
        console.error('[evaluacion.js] Error al guardar:', error);
        mostrarNotificacion('Error al guardar. Intenta de nuevo.', 'danger');
    } finally {
        submitBtn.disabled = false;
    }
}

/**
 * Construye el payload a partir del formulario.
 *
 * @param {HTMLFormElement} form
 * @returns {Object}
 */
function construirPayload(form) {
    const data = new FormData(form);
    return Object.fromEntries(data.entries());
}

/**
 * Envía la evaluación al backend y retorna el resultado con GAPs y % ajuste.
 *
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
async function enviarEvaluacion(payload) {
    const response = await fetch(ENDPOINT_EVALUACION, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body:    JSON.stringify(payload),
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    return response.json();
}

/**
 * Actualiza el DOM con el nuevo porcentaje de ajuste y los semáforos individuales.
 *
 * @param {Object} resultado - { porcentaje_ajuste: number, evaluaciones: Array }
 */
function refrescarResumenAjuste(resultado) {
    const badgeAjuste = document.getElementById('badge-ajuste-global');
    if (badgeAjuste) {
        badgeAjuste.textContent = `${resultado.porcentaje_ajuste}%`;
        actualizarClaseBadgeAjuste(badgeAjuste, resultado.porcentaje_ajuste);
    }

    resultado.evaluaciones.forEach((evaluacion) => {
        const semaforoEl = document.getElementById(`semaforo-${evaluacion.competencia_id}`);
        const gapEl      = document.getElementById(`gap-${evaluacion.competencia_id}`);

        if (semaforoEl) actualizarSemaforo(semaforoEl, evaluacion.semaforo);
        if (gapEl)      gapEl.textContent = evaluacion.gap ?? '—';
    });
}

/**
 * Aplica la clase visual correcta al badge de % ajuste global.
 *
 * @param {HTMLElement} badge
 * @param {number} porcentaje
 */
function actualizarClaseBadgeAjuste(badge, porcentaje) {
    badge.classList.remove('badge-ajuste--alto', 'badge-ajuste--medio', 'badge-ajuste--bajo');

    if (porcentaje >= 80) {
        badge.classList.add('badge-ajuste--alto');
    } else if (porcentaje >= 50) {
        badge.classList.add('badge-ajuste--medio');
    } else {
        badge.classList.add('badge-ajuste--bajo');
    }
}

/**
 * Muestra una notificación temporal tipo Bootstrap alert.
 *
 * @param {string} mensaje
 * @param {string} tipo - 'success' | 'danger' | 'warning'
 */
function mostrarNotificacion(mensaje, tipo) {
    const contenedor = document.getElementById('notificaciones');
    if (!contenedor) return;

    const alerta = document.createElement('div');
    alerta.className = `alert alert-${tipo} alert-dismissible fade show`;
    alerta.role      = 'alert';
    alerta.innerHTML = `${mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;

    contenedor.appendChild(alerta);
    setTimeout(() => alerta.remove(), 4000);
}

// Bind al formulario de evaluación
document.addEventListener('DOMContentLoaded', () => {
    const formEvaluacion = document.getElementById('form-evaluacion');
    if (formEvaluacion) {
        formEvaluacion.addEventListener('submit', guardarEvaluacion);
    }
});
```

---

## 5. Estructura HTML de la Vista One-to-One

```html
<!-- public/views/evaluacion.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Evaluación de Competencias — Gestor EFI</title>
    <link rel="stylesheet" href="/css/main.css">
    <link rel="stylesheet" href="/css/semaforos.css">
    <link rel="stylesheet" href="/css/evaluacion.css">
</head>
<body>
    <main class="container py-4">

        <!-- Resumen global de ajuste (se actualiza vía JS) -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <h1 class="h4 mb-0">Evaluación: Empleado #<span id="empleado-nombre"></span></h1>
            <span class="badge-ajuste badge-ajuste--medio" id="badge-ajuste-global">—%</span>
        </div>

        <!-- Zona de notificaciones -->
        <div id="notificaciones" role="status" aria-live="polite"></div>

        <!-- Formulario de evaluación -->
        <form id="form-evaluacion" novalidate>
            <input type="hidden" name="empleado_id" value="<?= $empleadoId ?>">
            <input type="hidden" name="periodo_id"  value="<?= $periodoId ?>">

            <div class="card mb-3" id="comp-<?= $comp['id'] ?>">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="semaforo semaforo--amarillo" id="semaforo-<?= $comp['id'] ?>"></span>
                    <strong><?= htmlspecialchars($comp['nombre']) ?></strong>
                    <input type="number" name="puntuacion[<?= $comp['id'] ?>]" class="form-control w-auto" min="<?= $escalaMin ?>" max="<?= $escalaMax ?>" step="0.01">
                    <span id="gap-<?= $comp['id'] ?>" class="text-muted small">GAP: —</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar Evaluación</button>
        </form>
    </main>

    <script src="/js/utils.js"      defer></script>
    <script src="/js/semaforo.js"   defer></script>
    <script src="/js/evaluacion.js" defer></script>
</body>
</html>
```

---

## 6. Reglas Frontend Obligatorias

| Regla | Razón |
|---|---|
| Fetch API, no `$.ajax()` ni jQuery | Fetch es estándar nativo, sin dependencias externas |
| `X-Requested-With: XMLHttpRequest` en headers | El backend PHP puede detectar requests AJAX con `isAjaxRequest()` |
| IDs semánticos en elementos dinámicos | `semaforo-{id}`, `gap-{id}`, `badge-ajuste-global` — facilita tests y JS |
| `defer` en todos los `<script>` | No bloquear el render del HTML |
| Sin lógica de negocio en JS | JS sólo manipula DOM y hace requests. El cálculo real lo hace el backend |
| Notificaciones no bloqueantes | `alert()` nativo está prohibido. Usar sistema de notificaciones propio |
| `htmlspecialchars()` en vistas PHP | Prevenir XSS en todos los datos renderizados desde BD |
| Escala min/max desde BD | Los atributos `min` y `max` del input se inyectan desde el servidor, no hardcodeados |
