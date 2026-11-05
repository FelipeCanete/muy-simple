<?php

use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/*
| Unit: lógica pura, sin framework ni BD.
| Feature: requests HTTP reales contra la app, con SQLite en memoria.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

/** PDF mínimo real: el validador revisa los magic bytes, no la extensión. */
function pdfUpload(string $name = 'contrato.pdf'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

/** Archivo de texto disfrazado de PDF (extensión y mime declarados falsos). */
function fakePdfUpload(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'txt');
    file_put_contents($path, 'esto no es un pdf');

    return new UploadedFile($path, 'malicioso.pdf', 'application/pdf', null, true);
}

/** Token de enlace válido (64 hex). */
function plainToken(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Envía el webhook firmado tal como lo haría el proveedor.
 * Permite alterar timestamp / secreto / firma para probar rechazos.
 */
function postWebhook(array $payload, ?int $timestamp = null, ?string $secret = null, ?string $signature = null): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp ??= time();
    $secret ??= config('signing.webhook.secret');
    $signature ??= 'sha256='.VerifyWebhookSignature::sign($secret, (string) $timestamp, $body);

    return test()->call('POST', '/api/webhooks/signatures', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_SIGNATURE' => $signature,
    ], $body);
}

/** Toda respuesta de error de la API debe tener forma problem+json. */
function assertProblem(TestResponse $response, int $status, string $code): TestResponse
{
    $response->assertStatus($status)
        ->assertHeader('Content-Type', 'application/problem+json')
        ->assertJsonStructure(['type', 'title', 'status', 'detail', 'code', 'instance'])
        ->assertJsonPath('status', $status)
        ->assertJsonPath('code', $code);

    return $response;
}
