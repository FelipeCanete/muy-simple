<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Signer;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->document = Document::factory()->create();
    $this->token = plainToken();
    $this->signer = Signer::factory()->for($this->document)->withToken($this->token)->create();
});

describe('ver el enlace', function () {
    it('muestra el documento y el estado del firmante', function () {
        $this->getJson("/api/sign/{$this->token}")
            ->assertOk()
            ->assertJsonPath('data.document.title', $this->document->title)
            ->assertJsonPath('data.signer.email', $this->signer->email)
            ->assertJsonPath('data.signer.status', 'pendiente')
            // El firmante no ve a los demás firmantes ni datos internos.
            ->assertJsonMissingPath('data.document.signers')
            ->assertJsonMissingPath('data.document.id')
            ->assertJsonMissingPath('data.signer.rut');
    });

    it('responde 404 con un token que no existe', function () {
        assertProblem($this->getJson('/api/sign/'.plainToken()), 404, 'invalid_token');
    });

    it('responde 404 con un token mal formado sin consultar la BD', function () {
        assertProblem($this->getJson('/api/sign/abc'), 404, 'not_found');
        assertProblem($this->getJson('/api/sign/'.str_repeat('Z', 64)), 404, 'not_found');
    });

    it('responde 410 si el enlace expiró', function () {
        $this->signer->update(['token_expires_at' => now()->subSecond()]);

        assertProblem($this->getJson("/api/sign/{$this->token}"), 410, 'token_expired');
    });

    it('sigue mostrando la firma realizada aunque el enlace ya haya expirado', function () {
        $this->signer->update(['signed_at' => now()->subDay(), 'token_expires_at' => now()->subSecond()]);

        $this->getJson("/api/sign/{$this->token}")->assertOk()->assertJsonPath('data.signer.status', 'firmado');
    });

    it('entrega el PDF al firmante', function () {
        Storage::fake('local');
        Storage::disk('local')->put($this->document->stored_path, '%PDF-1.4 demo');

        $this->get("/api/sign/{$this->token}/file")->assertOk()->assertHeader('Content-Type', 'application/pdf');
    });
});

describe('firmar', function () {
    it('registra la firma y recalcula el estado del documento', function () {
        $other = Signer::factory()->for($this->document)->create();

        $this->postJson("/api/sign/{$this->token}")
            ->assertOk()
            ->assertJsonPath('data.signer.status', 'firmado')
            ->assertJsonPath('data.document.status', 'firmado_parcial');

        $this->signer->refresh();
        expect($this->signer->signed_at)->not->toBeNull()
            ->and($this->signer->signed_via->value)->toBe('link')
            ->and($this->signer->signed_ip)->toBe('127.0.0.1')
            ->and($other->fresh()->signed_at)->toBeNull()
            ->and($this->document->fresh()->status)->toBe(DocumentStatus::FirmadoParcial);
    });

    it('marca el documento como completo cuando firma el último', function () {
        $otherToken = plainToken();
        Signer::factory()->for($this->document)->withToken($otherToken)->create();

        $this->postJson("/api/sign/{$this->token}")->assertJsonPath('data.document.status', 'firmado_parcial');
        $this->postJson("/api/sign/{$otherToken}")->assertJsonPath('data.document.status', 'completo');

        expect($this->document->fresh()->status)->toBe(DocumentStatus::Completo);
    });

    it('no permite firmar dos veces con el mismo enlace', function () {
        $this->postJson("/api/sign/{$this->token}")->assertOk();
        $signedAt = $this->signer->fresh()->signed_at;

        $this->travel(1)->minutes();
        assertProblem($this->postJson("/api/sign/{$this->token}"), 409, 'already_signed');

        expect($this->signer->fresh()->signed_at->equalTo($signedAt))->toBeTrue();
    });

    it('no permite firmar con un enlace expirado', function () {
        $this->signer->update(['token_expires_at' => now()->subSecond()]);

        assertProblem($this->postJson("/api/sign/{$this->token}"), 410, 'token_expired');
        expect($this->signer->fresh()->signed_at)->toBeNull();
    });

    it('responde 404 con un token desconocido', function () {
        assertProblem($this->postJson('/api/sign/'.plainToken()), 404, 'invalid_token');
    });

    it('limita los intentos por IP (fuerza bruta sobre tokens)', function () {
        foreach (range(1, 30) as $_) {
            $this->getJson('/api/sign/'.plainToken());
        }

        assertProblem($this->getJson('/api/sign/'.plainToken()), 429, 'too_many_requests');
    });
});
