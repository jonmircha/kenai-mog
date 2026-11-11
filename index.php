<?php

declare(strict_types=1);

/* ===========================================================================
   kEnAi MOG · Minimalist Onefile Generator
   ---------------------------------------------------------------------------
   Toda la app en UN archivo. En orden: configuración, conexión y arranque,
   funciones de datos, router de la API, payload inicial, HTML, CSS y JS.
   Portabilidad por encima de todo: suéltalo en un hosting y funciona.

   Demo: una lista de tareas. Es el patrón, no el producto; cámbialo por el tuyo.
   =========================================================================== */


/* ===========================================================================
   1. CONFIGURACIÓN
   =========================================================================== */

date_default_timezone_set('America/Mexico_City');
mb_internal_encoding('UTF-8');

const APP_NAME = 'kEnAi MOG';

/* La base vive en datos/ (fuera del alcance de HTTP por su .htaccess). El
   esquema va embebido aquí para que el proyecto siga siendo un solo archivo. */
const DB_FILE = __DIR__ . '/datos/mog.sqlite';
const ESQUEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS tareas (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    texto     TEXT NOT NULL,
    hecha     INTEGER NOT NULL DEFAULT 0,
    creada_en TEXT NOT NULL DEFAULT (datetime('now'))
);
SQL;


/* ===========================================================================
   2. CONEXIÓN Y ARRANQUE
   =========================================================================== */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $nueva = !is_file(DB_FILE);
    if ($nueva && !is_dir(dirname(DB_FILE))) {
        mkdir(dirname(DB_FILE), 0775, true);
    }

    $pdo = new PDO('sqlite:' . DB_FILE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');   // SQLite no lo recuerda entre conexiones
    $pdo->exec('PRAGMA busy_timeout = 5000'); // reintenta si otra escritura tiene el candado
    $pdo->exec('PRAGMA journal_mode = WAL');  // lecturas sin bloqueo mientras se escribe

    if ($nueva) {
        $pdo->exec(ESQUEMA);
    }

    return $pdo;
}

/* Abre una transacción de ESCRITURA con BEGIN IMMEDIATE: toma el candado desde
   el arranque (no el BEGIN diferido de beginTransaction(), que falla bajo
   concurrencia). Hace COMMIT al terminar, ROLLBACK si algo revienta. */
function mutar(callable $fn): void
{
    $pdo = db();
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $fn($pdo);
        $pdo->exec('COMMIT');
    } catch (\Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}


/* ===========================================================================
   3. DATOS
   ---------------------------------------------------------------------------
   El snapshot es el estado COMPLETO que el cliente repinta sin diffing: toda
   mutación devuelve esto mismo.
   =========================================================================== */

/** @return array<string,mixed> */
function snapshot(): array
{
    $tareas = db()->query('SELECT id, texto, hecha FROM tareas ORDER BY id DESC')->fetchAll();
    // Tipos limpios para el cliente (SQLite devuelve todo como texto).
    $tareas = array_map(static fn(array $t): array => [
        'id'    => (int) $t['id'],
        'texto' => $t['texto'],
        'hecha' => (bool) $t['hecha'],
    ], $tareas);

    $pendientes = count(array_filter($tareas, static fn(array $t): bool => !$t['hecha']));

    return [
        'tareas'     => $tareas,
        'total'      => count($tareas),
        'pendientes' => $pendientes,
    ];
}


/* ===========================================================================
   4. API
   ---------------------------------------------------------------------------
   Todas las mutaciones responden con el snapshot completo. 422 = validación,
   500 = cualquier otra cosa. El primer render NO pasa por aquí (ver bloque 5).
   =========================================================================== */

$accion = $_GET['api'] ?? null;

if ($accion !== null) {
    header('Content-Type: application/json; charset=UTF-8');
    try {
        switch ($accion) {
            case 'estado':
                echo json_encode(snapshot());
                break;

            case 'agregar':
                $texto = trim((string) ($_POST['texto'] ?? ''));
                if ($texto === '' || mb_strlen($texto) > 200) {
                    http_response_code(422);
                    echo json_encode(['error' => 'Escribe una tarea (máximo 200 caracteres).']);
                    break;
                }
                mutar(static function (PDO $pdo) use ($texto): void {
                    $pdo->prepare('INSERT INTO tareas (texto) VALUES (:t)')->execute([':t' => $texto]);
                });
                echo json_encode(snapshot());
                break;

            case 'alternar':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    http_response_code(422);
                    echo json_encode(['error' => 'Tarea inválida.']);
                    break;
                }
                mutar(static function (PDO $pdo) use ($id): void {
                    $pdo->prepare('UPDATE tareas SET hecha = 1 - hecha WHERE id = :id')->execute([':id' => $id]);
                });
                echo json_encode(snapshot());
                break;

            case 'eliminar':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id <= 0) {
                    http_response_code(422);
                    echo json_encode(['error' => 'Tarea inválida.']);
                    break;
                }
                mutar(static function (PDO $pdo) use ($id): void {
                    $pdo->prepare('DELETE FROM tareas WHERE id = :id')->execute([':id' => $id]);
                });
                echo json_encode(snapshot());
                break;

            default:
                http_response_code(404);
                echo json_encode(['error' => 'Acción desconocida.']);
        }
    } catch (\Throwable $e) {
        error_log('[kenai-mog] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        http_response_code(500);
        echo json_encode(['error' => 'Ocurrió un error interno.']);
    }
    exit;
}


/* ===========================================================================
   5. PAYLOAD INICIAL
   ---------------------------------------------------------------------------
   El primer render no hace ni una petición: el estado va embebido en BOOT.
   =========================================================================== */

$payload = json_encode(snapshot(), JSON_THROW_ON_ERROR);

/** Escapa para HTML. */
function h(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

?>
<!-- ===========================================================================
     6. HTML
     ======================================================================== -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= h(APP_NAME) ?> — Tareas</title>
    <link rel="icon" type="image/png" href="assets/img/favicon.png">

    <!-- =======================================================================
         7. CSS — en línea, con paleta propia e independiente de cualquier DS.
         ==================================================================== -->
    <style>
        :root {
            color-scheme: light;

            --marca: #b5652e;
            --marca-profunda: #6b4a33;
            --sobre-marca: #ffffff;

            --bg: #fcfcfc;
            --superficie: #ffffff;
            --texto: #202225;
            --texto-tenue: #6e6e6e;
            --borde: #eeeeee;
            --borde-control: #8a8a8a;
            --peligro: #c0392b;

            --radio: 0.5rem;
            --fuente: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100dvh;
            background: var(--bg);
            color: var(--texto);
            font-family: var(--fuente);
            line-height: 1.6;
        }

        .mog-envoltura {
            width: 100%;
            max-width: 34rem;
            margin-inline: auto;
            padding: 1.5rem 1rem 3rem;
        }

        .mog-cabecera {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 0.75rem;
        }

        .mog-cabecera h1 {
            font-size: 1.4rem;
            margin: 0;
        }

        .mog-cabecera strong {
            color: var(--marca);
        }

        .mog-lema {
            color: var(--texto-tenue);
            font-size: 0.95rem;
            margin: 0 0 1.5rem;
        }

        .mog-lema em {
            color: var(--texto);
            font-style: normal;
            font-weight: 600;
        }

        .mog-lema code {
            font-family: ui-monospace, "JetBrains Mono", "SF Mono", Menlo, monospace;
            font-size: 0.85em;
            background: color-mix(in srgb, var(--marca) 12%, transparent);
            padding: 0.1em 0.35em;
            border-radius: 0.3rem;
        }

        .mog-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .mog-input {
            flex: 1;
            font: inherit;
            color: var(--texto);
            background: var(--superficie);
            border: 1px solid var(--borde-control);
            border-radius: 0.4rem;
            padding: 0.55rem 0.7rem;
        }

        .mog-input:focus-visible {
            outline: 2px solid var(--marca);
            outline-offset: 1px;
        }

        .mog-btn {
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            border-radius: 0.4rem;
            padding: 0.55rem 1rem;
            background: var(--marca);
            color: var(--sobre-marca);
        }

        .mog-btn:hover {
            background: var(--marca-profunda);
        }

        .mog-error {
            color: var(--peligro);
            font-size: 0.9rem;
            min-height: 1.4em;
            margin: 0 0 0.75rem;
        }

        .mog-resumen {
            color: var(--texto-tenue);
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .mog-lista {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.5rem;
        }

        .mog-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: var(--superficie);
            border: 1px solid var(--borde);
            border-radius: var(--radio);
            padding: 0.6rem 0.8rem;
        }

        .mog-item__texto {
            flex: 1;
            cursor: pointer;
        }

        .mog-item--hecha .mog-item__texto {
            text-decoration: line-through;
            color: var(--texto-tenue);
        }

        .mog-x {
            font: inherit;
            cursor: pointer;
            background: transparent;
            border: 1px solid color-mix(in srgb, var(--peligro) 45%, transparent);
            color: var(--peligro);
            border-radius: 0.35rem;
            padding: 0.2rem 0.5rem;
            line-height: 1;
        }

        .mog-vacio {
            color: var(--texto-tenue);
        }

        .mog-pie {
            margin-top: 2rem;
            color: var(--texto-tenue);
            font-size: 0.8rem;
            text-align: center;
        }

        .mog-pie a {
            color: var(--marca);
        }
    </style>
</head>

<body>
    <main class="mog-envoltura">
        <header class="mog-cabecera">
            <img src="assets/img/kenai.png" alt="" width="30" height="35">
            <h1>kEnAi&nbsp;<strong>MOG</strong></h1>
        </header>

        <p class="mog-lema">
            <em>Minimalist Onefile Generator.</em> Toda la app en un archivo:
            PHP&nbsp;+&nbsp;SQLite, API por <code>?api=</code> y patrón snapshot.
            Sin dependencias, sin build. La demo es esta lista de tareas.
        </p>

        <form class="mog-form" data-action="agregar" autocomplete="off">
            <input class="mog-input" name="texto" maxlength="200" placeholder="Nueva tarea…" aria-label="Nueva tarea" required>
            <button class="mog-btn" type="submit">Agregar</button>
        </form>

        <p class="mog-error" id="error" role="alert"></p>
        <p class="mog-resumen" id="resumen"></p>

        <ul class="mog-lista" id="lista"></ul>

        <footer class="mog-pie">
            <p>Hecho con <a href="https://github.com/jonmircha/kenai-mog">kEnAi&nbsp;MOG</a> — un archivo, PHP + SQLite.</p>
        </footer>
    </main>

    <!-- =======================================================================
         7. JS — en línea. IIFE, estado único, un solo manejador por data-action,
         patrón snapshot (toda mutación devuelve el estado y se repinta entero).
         ==================================================================== -->
    <script>
        (() => {
            "use strict";

            const BOOT = <?= $payload ?>;
            const state = { ...BOOT };

            const $lista = document.getElementById("lista");
            const $resumen = document.getElementById("resumen");
            const $error = document.getElementById("error");

            const esc = (t) =>
                String(t).replace(/[&<>"']/g, (c) =>
                    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

            /* Pide al servidor y, si responde snapshot, reemplaza el estado y repinta. */
            async function llamar(accion, datos) {
                $error.textContent = "";
                const cuerpo = new URLSearchParams(datos || {});
                const res = await fetch(`?api=${accion}`, {
                    method: "POST",
                    headers: { "X-Requested-With": "fetch" },
                    body: cuerpo,
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    $error.textContent = json.error || "Algo salió mal.";
                    return;
                }
                Object.assign(state, json);
                pintar();
            }

            function pintar() {
                $resumen.textContent = state.total === 0
                    ? ""
                    : `${state.pendientes} pendiente(s) de ${state.total}`;

                if (state.tareas.length === 0) {
                    $lista.innerHTML = `<li class="mog-vacio">Sin tareas todavía. Agrega la primera.</li>`;
                    return;
                }

                $lista.innerHTML = state.tareas.map((t) => `
          <li class="mog-item ${t.hecha ? "mog-item--hecha" : ""}">
            <span class="mog-item__texto" data-action="alternar" data-id="${t.id}">${esc(t.texto)}</span>
            <button class="mog-x" type="button" data-action="eliminar" data-id="${t.id}" aria-label="Eliminar">✕</button>
          </li>`).join("");
            }

            /* Un solo manejador para todos los controles con data-action. */
            document.addEventListener("submit", (e) => {
                const form = e.target.closest('[data-action="agregar"]');
                if (!form) return;
                e.preventDefault();
                const input = form.elements.texto;
                const texto = input.value.trim();
                if (texto === "") return;
                llamar("agregar", { texto }).then(() => { input.value = ""; input.focus(); });
            });

            document.addEventListener("click", (e) => {
                const el = e.target.closest("[data-action]");
                if (!el || el.tagName === "FORM") return;
                const accion = el.dataset.action;
                if (accion === "alternar" || accion === "eliminar") {
                    llamar(accion, { id: el.dataset.id });
                }
            });

            pintar();
        })();
    </script>
</body>

</html>
