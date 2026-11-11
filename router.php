<?php

declare(strict_types=1);

/**
 * router.php — SOLO para el servidor de desarrollo de PHP:
 *
 *     php -S localhost:8091 router.php
 *
 * La app sigue siendo un solo archivo (index.php). Esto existe únicamente para
 * que, en desarrollo, `php -S` replique lo que en producción hace Apache con el
 * .htaccess: bloquear la carpeta de datos (que `php -S` a secas serviría tal
 * cual). En producción no se usa.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// 1) Bloquear carpetas internas (igual que el .htaccess).
if (preg_match('#^/(datos|contexto)(/|$)#', $uri)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

// 2) Archivos estáticos reales (assets/img): servir tal cual.
if ($uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

// 3) Todo lo demás → la app.
require __DIR__ . '/index.php';
