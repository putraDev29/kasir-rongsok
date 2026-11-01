<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

/*
|--------------------------------------------------------------------------
| Bind Public Path Dynamically for Shared Hosting (Hostinger, cPanel, etc.)
|--------------------------------------------------------------------------
| When Laravel is placed in a subdirectory (e.g. public_html/kasir-rongsok/kasir rongsok)
| or assets are served from the parent/root web directory, public_path() must
| point to where the web server serves static files (where index.php resides).
*/
if (env('PUBLIC_PATH')) {
    $customPublic = realpath(base_path(env('PUBLIC_PATH'))) ?: base_path(env('PUBLIC_PATH'));
    $app->usePublicPath($customPublic);
} elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
    if ($scriptDir && $scriptDir !== base_path() && file_exists($scriptDir . DIRECTORY_SEPARATOR . 'index.php')) {
        $app->usePublicPath($scriptDir);
    }
} elseif (file_exists(base_path('../index.php')) && (file_exists(base_path('../uploads')) || file_exists(base_path('../build')))) {
    $app->usePublicPath(realpath(base_path('..')));
}

return $app;
