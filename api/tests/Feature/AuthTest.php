<?php

use App\Models\User;

// Sanctum trata como SPA (sesión + CSRF) a los requests que vienen de un dominio "stateful".
beforeEach(fn () => $this->withHeader('Referer', 'http://localhost/login'));

it('inicia sesión con credenciales correctas', function () {
    $user = User::factory()->create(['password' => 'secreto-123']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'secreto-123'])
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonMissingPath('data.password');

    $this->assertAuthenticatedAs($user, 'web');
});

it('rechaza credenciales incorrectas con un mensaje genérico', function () {
    $user = User::factory()->create(['password' => 'secreto-123']);

    $response = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'otra']);

    assertProblem($response, 401, 'invalid_credentials');
    $this->assertGuest('web');
});

it('no revela si el email existe', function () {
    $response = $this->postJson('/api/login', ['email' => 'nadie@demo.cl', 'password' => 'x']);

    assertProblem($response, 401, 'invalid_credentials');
});

it('valida el formato de la entrada', function () {
    $response = $this->postJson('/api/login', ['email' => 'no-es-email']);

    assertProblem($response, 422, 'validation_failed')
        ->assertJsonValidationErrors(['email', 'password']);
});

it('limita los intentos de login (fuerza bruta)', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'mal'])->assertStatus(401);
    }

    assertProblem(
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'mal']),
        429,
        'too_many_requests',
    );
});

it('expone el usuario actual solo con sesión', function () {
    assertProblem($this->getJson('/api/me'), 401, 'unauthenticated');

    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $user->id);
});

it('cierra la sesión', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/logout')->assertNoContent();

    $this->assertGuest('web');
});
