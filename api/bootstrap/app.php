<?php

use App\Exceptions\Domain\DomainException;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Respuesta de error con formato RFC 9457 (problem+json). Todas las
 * respuestas de error de la API tienen esta misma forma.
 */
$problem = function (Request $request, int $status, string $code, string $title, string $detail, array $extra = []): JsonResponse {
    return new JsonResponse(
        array_merge([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'code' => $code,
            'instance' => '/'.ltrim($request->path(), '/'),
        ], $extra),
        $status,
        ['Content-Type' => 'application/problem+json'],
    );
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La API solo es alcanzable a través del nginx del front (red interna
        // de Docker), así que confiamos en sus X-Forwarded-* para la IP real.
        $middleware->trustProxies(at: '*');
        // Sesión + CSRF de Sanctum para el SPA servido desde el mismo origen.
        $middleware->statefulApi();
        $middleware->append(SecurityHeaders::class);
        // Esta app no tiene página de login en Laravel (vive en Angular): un
        // invitado en /api/* recibe 401 problem+json, nunca un redirect.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions) use ($problem): void {
        // Los errores de negocio son esperables: no ensucian el log de errores.
        $exceptions->dontReport([DomainException::class]);

        $exceptions->render(function (Throwable $e, Request $request) use ($problem) {
            if (! $request->is('api/*')) {
                return null;
            }

            return match (true) {
                $e instanceof DomainException => $problem(
                    $request, $e->status(), $e->errorCode(), $e->title(), $e->getMessage()
                ),

                $e instanceof ValidationException => $problem(
                    $request, 422, 'validation_failed', 'Datos inválidos',
                    'Revisa los campos marcados.', ['errors' => $e->errors()]
                ),

                $e instanceof AuthenticationException => $problem(
                    $request, 401, 'unauthenticated', 'No autenticado', 'Debes iniciar sesión.'
                ),

                $e instanceof HttpExceptionInterface => match ($e->getStatusCode()) {
                    404 => $problem($request, 404, 'not_found', 'No encontrado', 'El recurso solicitado no existe.'),
                    405 => $problem($request, 405, 'method_not_allowed', 'Método no permitido', 'Método HTTP no soportado en esta ruta.'),
                    419 => $problem($request, 419, 'csrf_mismatch', 'Sesión expirada', 'Recarga la página e inténtalo de nuevo.'),
                    429 => $problem($request, 429, 'too_many_requests', 'Demasiadas solicitudes', 'Espera un momento antes de reintentar.'),
                    default => $problem($request, $e->getStatusCode(), 'http_error', 'Error', 'No se pudo procesar la solicitud.'),
                },

                // Cualquier otro error: mensaje genérico, sin stack trace ni detalles internos.
                default => $problem(
                    $request, 500, 'internal_error', 'Error interno',
                    'Ocurrió un error inesperado. Inténtalo más tarde.'
                ),
            };
        });
    })->create();
