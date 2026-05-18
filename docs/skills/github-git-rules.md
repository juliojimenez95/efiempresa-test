# Git & GitHub Rules — Gestor de Competencias

> **Ámbito:** Control de Versiones · Repositorio Git  
> **Principio rector:** Historial limpio, descriptivo y reversible. Cada commit representa un cambio lógico único e independiente.

---

## 1. Commits Atómicos (Obligatorio)

Un **commit atómico** es aquel que contiene exactamente **un único cambio lógico**. No se deben mezclar múltiples cambios que no tengan relación directa entre sí en un mismo commit.

### Reglas de Oro de la Atomicidad
- **Independencia funcional:** El proyecto debe compilar y funcionar correctamente después de cada commit. Nunca dejes el repositorio en un estado roto "temporalmente".
- **Sin mezclas:** Si estás refactorizando la clase de conexión a la base de datos (`Database.php`) y al mismo tiempo encuentras un error visual en el CSS del semáforo, **haz dos commits separados**. Uno para el backend y otro para el frontend.
- **Mensajes precisos:** El mensaje de commit debe describir con exactitud el cambio realizado, sin incluir descripciones genéricas como "cambios varios" o "arreglos".
- **Facilidad de reversión:** Si un commit causa un fallo en producción, debe ser posible hacer `git revert` de ese commit específico sin perder otros desarrollos que sí funcionaban.

---

## 2. Prefijos de Conventional Commits

Adoptamos el estándar **Conventional Commits** para estructurar los mensajes. Todos los commits deben seguir el formato:

```
<tipo>(<alcance>): <descripción corta en imperativo o presente>

[cuerpo opcional para detallar el porqué del cambio]
```

### Tipos Permitidos

| Tipo | Propósito | Ejemplo |
|---|---|---|
| **`feat`** | Una nueva funcionalidad para el usuario. | `feat(api): implementar endpoint para registrar evaluaciones` |
| **`fix`** | Solución a un error o bug del sistema. | `fix(db): corregir clave única en tabla de perfiles objetivo` |
| **`chore`** | Tareas de mantenimiento, dependencias, Docker, gitignore o roadmaps. | `chore(docker): agregar MySQL 8.0 y scripts de inicialización` |
| **`refactor`** | Reestructuración de código sin alterar su comportamiento externo. | `refactor(db): migrar consulta inline de evaluaciones a clase Repository` |
| **`docs`** | Modificaciones exclusivas de documentación. | `docs(skills): crear directrices de control de versiones Git` |
| **`style`** | Cambios de formato que no afectan la lógica (espacios, punto y coma, identación). | `style(css): ajustar colores semánticos en hoja de estilos de semáforos` |
| **`perf`** | Mejoras de rendimiento en consultas SQL o procesamiento en backend. | `perf(matriz): indexar consultas de evaluaciones para acelerar la matriz` |
| **`test`** | Creación o actualización de pruebas unitarias o de integración. | `test(service): agregar pruebas unitarias para cálculo de gap y ajuste` |
| **`build`** | Cambios en el sistema de construcción o dependencias externas (ej. composer). | `build(deps): actualizar versión mínima de PHP en composer.json` |
| **`ci`** | Configuración de flujos de Integración Continua (ej. GitHub Actions). | `ci(github): configurar validador de Conventional Commits para PRs` |
| **`revert`** | Revertir un commit anterior que causó problemas. | `revert: feat(api): implementar endpoint para registrar evaluaciones` |

### Reglas para los Mensajes
1. **Idioma:** Se recomienda redactar la descripción en español, utilizando un tono claro e imperativo.
2. **Minúsculas:** El tipo y el alcance siempre van en minúsculas.
3. **Puntuación:** No termines la descripción corta con un punto final.
4. **Cuerpo explicativo:** Si el cambio es complejo, deja una línea en blanco y explica detalladamente el **porqué** del cambio (no el cómo).

---

## 3. Ramas del Repositorio (Branching Model)

Las ramas de trabajo deben alinearse directamente con los tipos de Conventional Commits, utilizando el formato:

```
<tipo>/<descripción-breve-con-guiones>
```

### Ejemplos de Ramas
- Para una funcionalidad: `feat/one-to-one-view`
- Para un bug: `fix/db-connection-retry`
- Para documentación: `docs/git-rules-update`
- Para mantenimiento: `chore/docker-mysql-volume`

*Nota:* No uses nombres de personas (`julio/fix-db`), ni códigos oscuros. Las ramas deben ser autoexplicativas y seguir el mismo estándar de minúsculas y guiones.

---

## 4. Flujo de Trabajo en Git

1. **Crear una rama limpia desde `main`:**
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feat/one-to-one-view
   ```
2. **Desarrollar y hacer commits atómicos:**
   ```bash
   # Paso 1: Crear HTML base
   git add public/views/evaluacion.php
   git commit -m "feat(views): crear estructura HTML para evaluación one-to-one"
   
   # Paso 2: Crear lógica JS
   git add public/js/evaluacion.js
   git commit -m "feat(js): implementar fetch asíncrono para envío de datos"
   ```
3. **Validar antes de fusionar:**
   - Asegurarse de que no existan advertencias de linting.
   - Probar localmente que la base de datos y la interfaz funcionen integradas.
