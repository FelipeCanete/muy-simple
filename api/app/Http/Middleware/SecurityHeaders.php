<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Headers de seguridad para todas las respuestas de la API (OWASP A05). */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        // Los enlaces de firma llevan el token en la URL: nunca enviarlo como Referer.
        $headers->set('Referrer-Policy', 'no-referrer');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $headers->remove('X-Powered-By');

        // Las respuestas JSON no deben ejecutar ni embeber nada.
        // A los PDF no les ponemos CSP para no romper el visor del navegador.
        if (! $response instanceof StreamedResponse) {
            $headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'");
            $headers->set('Cache-Control', 'no-store, private');
        }

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
