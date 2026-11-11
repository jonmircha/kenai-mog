# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/)
y el proyecto sigue [Versionado Semántico](https://semver.org/lang/es/).

## [Unreleased]

## [1.0.0] - 2026-11-11

### Added

- **Esqueleto funcional de kEnAi MOG — Minimalist Onefile Generator.** Toda la
  app en un solo `index.php`, PHP vanilla + SQLite, sin dependencias ni build.
  - **Siete bloques** en orden: configuración → conexión/arranque → datos → API
    → payload inicial → HTML → CSS/JS en línea.
  - **SQLite** vía PDO con `foreign_keys`, `busy_timeout` y `journal_mode = WAL`;
    esquema embebido y autoinicializado en el primer arranque; mutaciones en
    `BEGIN IMMEDIATE`.
  - **API en el mismo archivo** por `?api=<acción>`, con 422 para validación y
    500 para el resto.
  - **Patrón snapshot**: toda mutación devuelve el estado completo y el cliente
    repinta sin diffing; el primer render va embebido en `const BOOT`.
  - **JS** como IIFE `"use strict"`, estado único y un solo manejador por
    `data-action` (sin `onclick`).
  - **CSS y JS en línea** con **paleta propia** independiente de cualquier DS
    (tema claro, la misma identidad que MSG y MAG), más el favicon e imagotipo de marca.
  - **Demo**: una lista de tareas (agregar, alternar, eliminar).
  - **`.htaccess`** con HTTPS forzado y bloqueo de la carpeta de datos.

[Unreleased]: https://github.com/jonmircha/kenai-mog/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jonmircha/kenai-mog/releases/tag/v1.0.0
