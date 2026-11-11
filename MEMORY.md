# MEMORY.md

Dónde nos quedamos en **kEnAi MOG**. Estado, pendientes y decisiones con su porqué.

## Estado

- **2026-11-11** — Nace el esqueleto funcional mínimo (v1.0.0 en preparación).
  Un solo `index.php` con los 7 bloques; demo de lista de tareas (agregar,
  alternar, eliminar) sobre SQLite autoinicializado. Verificado con `php -l`,
  la API por `curl` y el navegador.

## Decisiones

- **Esquema embebido en `index.php`** (no un `.sql` aparte, como sí hace my-cash):
  para que el proyecto sea de verdad un solo archivo. Se aplica en el primer
  arranque.
- **Paleta y JS/CSS en línea, desvinculados del kEnAi DS.** El repo es público y
  reutilizable; no puede depender del DS. Se copió solo la paleta principal a
  valores literales, más el favicon y el imagotipo como assets locales.
- **Sin login ni CSRF, a propósito.** MOG es para un usuario (local/personal).
  En cuanto hay varios usuarios o roles, el molde correcto es MAG. Se dejó dicho
  en el README para no difuminar la frontera entre las dos arquitecturas.
- **La base en `datos/`** (no junto a `index.php`) para poder bloquearla con un
  `.htaccess` deny-all sin tocar el resto.

## Pendientes

- [ ] `git init`, primer commit y push a `github.com/jonmircha/kenai-mog` — **lo
      hace Jonathan**.
- [ ] Valorar un pequeño export/import JSON (como en my-cash) si se quiere mostrar
      respaldo de datos en el esqueleto.
