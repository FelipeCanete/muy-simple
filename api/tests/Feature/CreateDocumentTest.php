<?php

use App\Models\Document;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
});

function validPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Contrato de arriendo',
        'file' => pdfUpload(),
        'signers' => [
            ['email' => 'ana@demo.cl', 'rut' => '12.345.678-5'],
            ['email' => 'bruno@demo.cl', 'rut' => '11111111-1'],
        ],
    ], $overrides);
}

it('crea el documento en estado pendiente con un enlace por firmante', function () {
    $response = $this->actingAs($this->user)->post('/api/documents', validPayload(), ['Accept' => 'application/json']);

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Contrato de arriendo')
        ->assertJsonPath('data.status', 'pendiente')
        ->assertJsonPath('data.signers_total', 2)
        ->assertJsonPath('data.signers_signed', 0)
        ->assertJsonCount(2, 'signing_links')
        ->assertJsonMissingPath('data.stored_path');

    $document = Document::sole();
    expect($document->created_by)->toBe($this->user->id);
    Storage::disk('local')->assertExists($document->stored_path);
    expect($document->stored_path)->not->toContain('contrato'); // nombre aleatorio en disco

    // RUT normalizado y token guardado SOLO como hash.
    $link = $response->json('signing_links.0');
    $token = basename($link['url']);
    $signer = Signer::findOrFail($link['signer_id']);

    expect($link['url'])->toStartWith(config('app.url').'/firmar/')
        ->and($token)->toMatch('/^[a-f0-9]{64}$/')
        ->and($signer->rut)->toBe('12345678-5')
        ->and($signer->token_hash)->toBe(hash('sha256', $token))
        ->and(Signer::where('token_hash', $token)->exists())->toBeFalse();
});

it('genera tokens distintos para cada firmante', function () {
    $response = $this->actingAs($this->user)->post('/api/documents', validPayload(), ['Accept' => 'application/json']);

    $urls = collect($response->json('signing_links'))->pluck('url');
    expect($urls->unique())->toHaveCount(2);
});

it('exige autenticación', function () {
    assertProblem($this->post('/api/documents', validPayload(), ['Accept' => 'application/json']), 401, 'unauthenticated');

    expect(Document::count())->toBe(0);
});

it('rechaza datos inválidos', function (array $overrides, string $field) {
    $response = $this->actingAs($this->user)->post('/api/documents', validPayload($overrides), ['Accept' => 'application/json']);

    assertProblem($response, 422, 'validation_failed')->assertJsonValidationErrors([$field]);
    expect(Document::count())->toBe(0);
    Storage::disk('local')->assertDirectoryEmpty('/');
})->with([
    'sin título' => [['title' => ''], 'title'],
    'sin archivo' => [['file' => null], 'file'],
    'sin firmantes' => [['signers' => []], 'signers'],
    'email inválido' => [['signers' => [['email' => 'no-es-email', 'rut' => '12345678-5']]], 'signers.0.email'],
    'RUT con DV incorrecto' => [['signers' => [['email' => 'a@demo.cl', 'rut' => '12345678-9']]], 'signers.0.rut'],
    'firmante sin RUT' => [['signers' => [['email' => 'a@demo.cl']]], 'signers.0.rut'],
    'campos extra en firmante' => [['signers' => [['email' => 'a@demo.cl', 'rut' => '12345678-5', 'admin' => true]]], 'signers.0'],
]);

it('rechaza un archivo de texto disfrazado de PDF', function () {
    $response = $this->actingAs($this->user)->post('/api/documents', validPayload(['file' => fakePdfUpload()]), ['Accept' => 'application/json']);

    assertProblem($response, 422, 'validation_failed')
        ->assertJsonPath('errors.file.0', 'El archivo debe ser un PDF válido.');
});

it('rechaza un PDF que supera el tamaño máximo', function () {
    config(['signing.max_file_kb' => 0]);

    $response = $this->actingAs($this->user)->post('/api/documents', validPayload(), ['Accept' => 'application/json']);

    assertProblem($response, 422, 'validation_failed')->assertJsonValidationErrors(['file']);
});

it('detecta firmantes duplicados aunque estén escritos distinto', function (array $signers, string $field) {
    $response = $this->actingAs($this->user)->post('/api/documents', validPayload(['signers' => $signers]), ['Accept' => 'application/json']);

    assertProblem($response, 422, 'validation_failed')->assertJsonValidationErrors([$field]);
})->with([
    'mismo RUT con y sin puntos' => [[
        ['email' => 'a@demo.cl', 'rut' => '12.345.678-5'],
        ['email' => 'b@demo.cl', 'rut' => '12345678-5'],
    ], 'signers.1.rut'],
    'mismo email con mayúsculas' => [[
        ['email' => 'ana@demo.cl', 'rut' => '12345678-5'],
        ['email' => 'ANA@demo.cl', 'rut' => '11111111-1'],
    ], 'signers.1.email'],
]);

it('limita la cantidad de firmantes', function () {
    config(['signing.max_signers' => 1]);

    $response = $this->actingAs($this->user)->post('/api/documents', validPayload(), ['Accept' => 'application/json']);

    assertProblem($response, 422, 'validation_failed')->assertJsonValidationErrors(['signers']);
});

it('guarda solo el nombre base del archivo original', function () {
    $this->actingAs($this->user)->post('/api/documents', validPayload(['file' => pdfUpload('../../etc/passwd.pdf')]), ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.original_name', 'passwd.pdf');
});
