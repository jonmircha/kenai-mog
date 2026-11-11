# CLAUDE.md

Guía para Claude Code (claude.ai/code) al trabajar en este repositorio.

## Qué es esto

**kEnAi MOG — Minimalist Onefile Generator.** Una herramienta de un solo
propósito con **toda la app en `index.php`**: PHP, HTML, CSS y JavaScript en un
archivo. **Sin dependencias, sin build.** El valor es la portabilidad: un archivo
que se suelta en cualquier hosting con PHP. No lo partas en varios archivos ni le
agregues un framework; si el proyecto crece a roles o multiusuario, eso ya es
[kEnAi MAG](https://github.com/jonmircha/kenai-mag), no MOG.

## Comandos

```bash
# Servidor de desarrollo — usa router.php (replica el .htaccess en dev):
php -S localhost:8091 router.php

# Comprobar sintaxis (no hay tests ni build):
php -l index.php
```

La app es `index.php`; `router.php` existe **solo para el servidor de
desarrollo** (hace que `php -S` bloquee `datos/`, que si no serviría la base
SQLite tal cual). En producción no se usa. Verificación: `php -l`, correr el
servidor y probar la API con `curl` o el navegador; el `.htaccess` solo se
verifica de verdad contra Apache.

## Los siete bloques de index.php (en este orden)

1. **Configuración** — `date_default_timezone_set`, constantes, `ESQUEMA` (el SQL embebido).
2. **Conexión y arranque** — `db()` (PDO + PRAGMAs: foreign_keys, busy_timeout, WAL) y `mutar()` (BEGIN IMMEDIATE).
3. **Datos** — `snapshot()`: el estado completo que el cliente repinta.
4. **API** — router por `?api=<acción>`; 422 validación, 500 el resto; cada mutación responde `snapshot()`.
5. **Payload inicial** — `$payload` embebido como `const BOOT` (el primer render no pide nada).
6. **HTML** — el marcado.
7. **CSS y JS en línea** — paleta propia en `:root`; IIFE `"use strict"` con `state` y un manejador por `data-action`.

Mantén ese orden: es el contrato del patrón.

## Reglas

- **Patrón snapshot**: toda mutación devuelve el estado completo; el cliente hace
  `Object.assign(state, json)` y repinta. No actualices el DOM a mano por pieza.
- **Escrituras en `mutar()`** (BEGIN IMMEDIATE); **siempre sentencias preparadas**.
- **SQLite devuelve todo como texto**: normaliza tipos en `snapshot()` (int/bool)
  antes de mandarlos al cliente.
- **JS sin `onclick`**: todo por `data-action` y un único listener. Nada en el
  ámbito global (IIFE).
- **Escapa**: `h()` en PHP para el HTML inicial, `esc()` en JS para lo que pintas.
- **La base vive en `datos/`** (bloqueada por `.htaccess`). Al cambiar el esquema,
  recuerda que solo se aplica en el primer arranque: borra `datos/mog.sqlite` en
  desarrollo para regenerarla.
- **Un usuario**. No hay login ni CSRF a propósito (es el alcance de MOG). Si
  hace falta, el proyecto es otro (MAG).

## Diseño

El `<style>` en línea trae una **paleta propia e independiente** de cualquier DS
(valores literales, tema claro; la misma identidad que MSG y MAG). No la
vincules a un sistema de diseño externo: la gracia es que el archivo sea autónomo.
