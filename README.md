# 🦊 kEnAi MOG — Minimalist Onefile Generator

[![Lint](https://github.com/jonmircha/kenai-mog/actions/workflows/lint.yml/badge.svg)](https://github.com/jonmircha/kenai-mog/actions/workflows/lint.yml)
[![Versión](https://img.shields.io/badge/versión-1.0.0-blue.svg)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](https://www.php.net/supported-versions.php)
[![Licencia: MIT](https://img.shields.io/badge/licencia-MIT-green.svg)](LICENSE)

Una **herramienta de un solo propósito en un solo archivo**: toda la app —PHP,
HTML, CSS y JavaScript— vive en `index.php`. **Cero dependencias, cero build.**
Portabilidad por encima de todo: lo sueltas en cualquier hosting con PHP y
funciona.

Es el molde para utilidades pequeñas, de **un usuario**, con poca superficie. Si
necesitas roles o varios usuarios escribiendo a la vez, usa
[kEnAi MAG](https://github.com/jonmircha/kenai-mag); para un sitio de contenido,
[kEnAi MSG](https://github.com/jonmircha/kenai-msg).

> **M**inimalist **O**nefile **G**enerator. La demo es una lista de tareas: es el
> **patrón**, no el producto. Cámbialo por tu herramienta.

---

## 📋 El patrón (siete bloques en un archivo)

`index.php` está ordenado así, de arriba abajo:

1. **Configuración** — zona horaria, constantes, el esquema SQL embebido.
2. **Conexión y arranque** — PDO + `PRAGMA` (WAL) y creación de la base al vuelo.
3. **Datos** — el _snapshot_: el estado completo que el cliente repinta.
4. **API** — router por `?api=<acción>`; 422 para validación, 500 para el resto.
5. **Payload inicial** — el estado embebido en `const BOOT` (el primer render no pide nada).
6. **HTML** — el marcado.
7. **CSS y JS en línea** — paleta propia + un IIFE con estado único y `data-action`.

### Patrón _snapshot_

Toda mutación (`agregar`, `alternar`, `eliminar`) responde con el **estado
completo**. El cliente reemplaza su copia y **repinta todo sin diffing**. Menos
código, cero desincronización entre servidor y pantalla.

---

## 🚀 Uso

### Requisitos

- **PHP 8.1 o superior** (recomendado **8.3+**) con **PDO SQLite** (viene de serie).

### Correr

```bash
git clone https://github.com/jonmircha/kenai-mog.git
cd kenai-mog
php -S localhost:8091 router.php
```

Abre `http://localhost:8091`. En el primer arranque se crea la base en
`datos/mog.sqlite` con su esquema.

> La app es `index.php` a secas. El `router.php` existe **solo para el servidor
> de desarrollo**: hace que `php -S` bloquee `datos/` igual que Apache con el
> `.htaccess` (si no, `php -S` serviría la base SQLite tal cual). En producción
> no se usa.

---

## 📁 Estructura

```
kenai-mog/
├── index.php          # TODA la app: PHP + HTML + CSS + JS, en 7 bloques
├── router.php         # solo para el servidor de desarrollo (replica el .htaccess)
├── .htaccess          # HTTPS forzado + bloqueo de datos/
├── datos/             # (bloqueada) la base SQLite vive aquí
├── assets/img/        # favicon + imagotipo
└── contexto/          # (bloqueada) insumos para la IA; no es parte de la app
```

---

## 🎨 Hazlo tuyo

- **El dominio**: cambia el esquema (`ESQUEMA`), las acciones de la API y el
  render. La demo de tareas es el andamio.
- **La marca**: edita las variables `:root` del `<style>` en línea y reemplaza
  `assets/img/favicon.png` y `assets/img/kenai.png`.

> La paleta viene inspirada en el [kEnAi DS](https://kenai.jonmircha.com), pero
> está **en línea y es independiente a propósito**: el archivo no depende de
> ningún sistema de diseño, para que lo uses donde quieras.

---

## 🔒 Seguridad y alcance

- **SQLite** con sentencias preparadas y escrituras en `BEGIN IMMEDIATE`.
- **`.htaccess`**: HTTPS forzado (salvo localhost) y bloqueo de `datos/` (con su
  propio `.htaccess` deny-all) y de archivos `.sqlite`/`.sql`/`.log`.
- **Pensado para un usuario** (local o personal). No trae login ni CSRF: si vas a
  exponerlo a varias personas, ese ya no es el caso de MOG — salta a
  [kEnAi MAG](https://github.com/jonmircha/kenai-mag), que trae auth, roles y CSRF.

---

## 📝 Licencia

**MIT** — ver [`LICENSE`](LICENSE).

## 👤 Autor

[**Jonathan MirCha**](https://jonmircha.com)
