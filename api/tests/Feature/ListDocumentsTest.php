<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Signer;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('lista los documentos con su estado y avance de firmas', function () {
    $doc = Document::factory()->for($this->user, 'creator')->create(['status' => DocumentStatus::FirmadoParcial]);
    Signer::factory()->for($doc)->signed()->create();
    Signer::factory()->for($doc)->create();

    $this->actingAs($this->user)->getJson('/api/documents')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $doc->id)
        ->assertJsonPath('data.0.status', 'firmado_parcial')
        ->assertJsonPath('data.0.status_label', 'firmado parcial')
        ->assertJsonPath('data.0.signers_total', 2)
        ->assertJsonPath('data.0.signers_signed', 1)
        ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'last_page', 'total']]);
});

it('solo muestra los documentos del usuario autenticado', function () {
    Document::factory()->for($this->user, 'creator')->create(['title' => 'Mío']);
    Document::factory()->create(['title' => 'De otro']);

    $this->actingAs($this->user)->getJson('/api/documents')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Mío');
});

it('filtra por estado', function () {
    Document::factory()->for($this->user, 'creator')->create(['status' => DocumentStatus::Pendiente]);
    Document::factory()->for($this->user, 'creator')->create(['status' => DocumentStatus::Completo]);

    $this->actingAs($this->user)->getJson('/api/documents?status=completo')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'completo');
});

it('rechaza un filtro de estado desconocido', function () {
    assertProblem($this->actingAs($this->user)->getJson('/api/documents?status=borrador'), 422, 'validation_failed')
        ->assertJsonValidationErrors(['status']);
});

it('pagina los resultados', function () {
    Document::factory()->count(3)->for($this->user, 'creator')->create();

    $this->actingAs($this->user)->getJson('/api/documents?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);
});

it('nunca expone tokens, rutas internas ni IPs', function () {
    $doc = Document::factory()->for($this->user, 'creator')->create();
    Signer::factory()->for($doc)->signed()->create(['signed_ip' => '10.0.0.1']);

    $json = $this->actingAs($this->user)->getJson("/api/documents/{$doc->id}")->assertOk()->getContent();

    expect($json)->not->toContain('token_hash')
        ->not->toContain('stored_path')
        ->not->toContain($doc->stored_path)
        ->not->toContain('10.0.0.1');
});

it('muestra el detalle con los firmantes', function () {
    $doc = Document::factory()->for($this->user, 'creator')->create();
    $signer = Signer::factory()->for($doc)->create(['rut' => '12345678-5']);

    $this->actingAs($this->user)->getJson("/api/documents/{$doc->id}")
        ->assertOk()
        ->assertJsonPath('data.signers.0.id', $signer->id)
        ->assertJsonPath('data.signers.0.rut', '12345678-5')
        ->assertJsonPath('data.signers.0.status', 'pendiente');
});

it('responde 404 al pedir el documento de otro usuario (sin revelar que existe)', function () {
    $ajeno = Document::factory()->create();

    assertProblem($this->actingAs($this->user)->getJson("/api/documents/{$ajeno->id}"), 404, 'not_found');
    assertProblem($this->actingAs($this->user)->getJson("/api/documents/{$ajeno->id}/file"), 404, 'not_found');
});

it('descarga el PDF propio', function () {
    Storage::fake('local');
    $doc = Document::factory()->for($this->user, 'creator')->create();
    Storage::disk('local')->put($doc->stored_path, '%PDF-1.4 demo');

    $this->actingAs($this->user)->get("/api/documents/{$doc->id}/file")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('exige autenticación para listar', function () {
    assertProblem($this->getJson('/api/documents'), 401, 'unauthenticated');
});
