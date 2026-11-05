<?php

namespace App\Http\Middleware;

use App\Exceptions\Domain\InvalidWebhookSignature;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifica que el webhook venga de quien comparte nuestro secreto.
 *
 *   X-Timestamp: <unix seconds>
 *   X-Signature: sha256=<hex(HMAC_SHA256(secret, "{timestamp}.{raw_body}"))>
 *
 * Incluir el timestamp en lo firmado + la ventana de tolerancia evita
 * que un request capturado se pueda re-enviar más tarde (replay).
 */
class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('signing.webhook.secret');

        if ($secret === '') {
            // Fallar cerrado: sin secreto configurado no aceptamos nada.
            Log::error('webhook.sin_secreto_configurado');
            throw new InvalidWebhookSignature;
        }

        $timestamp = (string) $request->header('X-Timestamp', '');
        $signature = (string) $request->header('X-Signature', '');

        if (! ctype_digit($timestamp)
            || abs(time() - (int) $timestamp) > config('signing.webhook.tolerance_seconds')) {
            $this->reject($request, 'timestamp_fuera_de_ventana');
        }

        $expected = 'sha256='.self::sign($secret, $timestamp, $request->getContent());

        // Comparación en tiempo constante (evita timing attacks).
        if (! hash_equals($expected, $signature)) {
            $this->reject($request, 'firma_invalida');
        }

        return $next($request);
    }

    public static function sign(string $secret, string $timestamp, string $body): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    private function reject(Request $request, string $reason): never
    {
        Log::warning('webhook.rechazado', ['reason' => $reason, 'ip' => $request->ip()]);

        throw new InvalidWebhookSignature;
    }
}
