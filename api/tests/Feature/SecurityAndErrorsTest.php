<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

it('agrega headers de seguridad a las respuestas de la API', function () {
    $this->getJson('/api/documents')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
        ->assertHeaderMissing('X-Powered-By');
});

it('no habilita CORS para orígenes externos', function () {
    $this->withHeader('Origin', 'https://sitio-malicioso.example')
        ->getJson('/api/documents')
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});

it('responde 401 (no 500 ni redirect) a una navegación del navegador sin sesión', function () {
    // Caso real: abrir el PDF en otra pestaña sin cookie de sesión válida.
    $response = $this->get('/api/documents/01a1ad3a-219d-7148-b2c3-12b40cc1d5ff/file', ['Accept' => 'text/html']);

    assertProblem($response, 401, 'unauthenticated');
});

it('responde 404 en formato problem+json para rutas inexistentes', function () {
    assertProblem($this->getJson('/api/no-existe'), 404, 'not_found');
});

it('responde 405 en formato problem+json para métodos no soportados', function () {
    assertProblem($this->deleteJson('/api/login'), 405, 'method_not_allowed');
});

it('oculta los detalles internos de errores inesperados', function () {
    config(['app.debug' => false]);
    Route::middleware('api')->get('/api/_boom', fn () => throw new RuntimeException('SQLSTATE secreto en /var/www'));

    $response = $this->getJson('/api/_boom');

    assertProblem($response, 500, 'internal_error');
    expect($response->getContent())
        ->not->toContain('SQLSTATE')
        ->not->toContain('/var/www')
        ->not->toContain('trace');
});

it('el seeder crea el usuario demo de forma idempotente', function () {
    $this->seed();
    $this->seed();

    expect(User::where('email', config('signing.demo_user.email'))->count())->toBe(1);
});
