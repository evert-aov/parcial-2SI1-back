<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JWTMiddleware::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponse::class,
        ]);
        
        // Aplicar middleware para todas las rutas API
        $middleware->group('api', [
            \App\Http\Middleware\ForceJsonResponse::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Configurar para que siempre devuelva JSON en las rutas API
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                // Para API, devolver un payload mínimo con mensaje según el código
                $statusCode = 500;
                if (method_exists($e, 'getStatusCode')) {
                    $statusCode = $e->getStatusCode();
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $statusCode = 422;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                    $statusCode = 404;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                    $statusCode = 405;
                } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
                    || $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                    $statusCode = 401;
                }

                $messages = [
                    401 => 'No autorizado',
                    404 => 'No encontrado',
                    405 => 'Método no permitido',
                    422 => 'Error de validación',
                    500 => 'Error interno del servidor',
                ];
                $message = $messages[$statusCode] ?? 'Error';
                return response()->json(['message' => $message], $statusCode);
            }
        });
    })->create();
