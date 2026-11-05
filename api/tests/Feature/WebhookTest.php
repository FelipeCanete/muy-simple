<?php

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Signer;
use App\Models\WebhookEvent;

beforeEach(function () {
    $this->document = Document::factory()->create();
    $this->signer = Signer::factory()->for($this->document)->create();
    $this->other = Signer::factory()->for($this->document)->create();
});

it('marca al firmante como firmado y recalcula el estado', function () {
    postWebhook(['event_id' => 'evt_1', 'signer_id' => $this->signer->id])
        ->assertOk()
        ->assertJsonPath('data.signer_status', 'firmado')
        ->assertJsonPath('data.document_status', 'firmado_parcial')
        ->assertJsonPath('data.duplicate_event', false)
        ->assertJsonPath('data.already_signed', false);

    $this->signer->refresh();
    expect($this->signer->signed_via->value)->toBe('webhook')
        ->and($this->document->fresh()->status)->toBe(DocumentStatus::FirmadoParcial);

    postWebhook(['event_id' => 'evt_2', 'signer_id' => $this->other->id])
        ->assertOk()
        ->assertJsonPath('data.document_status', 'completo');
});

it('es idempotente: el mismo event_id no se procesa dos veces', function () {
    postWebhook(['event_id' => 'evt_repetido', 'signer_id' => $this->signer->id])->assertOk();
    $signedAt = $this->signer->fresh()->signed_at;

    $this->travel(5)->minutes();

    postWebhook(['event_id' => 'evt_repetido', 'signer_id' => $this->signer->id])
        ->assertOk()
        ->assertJsonPath('data.duplicate_event', true);

    expect(WebhookEvent::count())->toBe(1)
        ->and($this->signer->fresh()->signed_at->equalTo($signedAt))->toBeTrue();
});

it('responde OK sin cambios si el firmante ya había firmado por enlace', function () {
    $this->signer->update(['signed_at' => now()->subHour(), 'signed_via' => 'link']);

    postWebhook(['event_id' => 'evt_tarde', 'signer_id' => $this->signer->id])
        ->assertOk()
        ->assertJsonPath('data.already_signed', true)
        ->assertJsonPath('data.duplicate_event', false);

    expect($this->signer->fresh()->signed_via->value)->toBe('link');
});

it('acepta la firma aunque el enlace del firmante haya expirado', function () {
    $this->signer->update(['token_expires_at' => now()->subDay()]);

    postWebhook(['event_id' => 'evt_exp', 'signer_id' => $this->signer->id])
        ->assertOk()
        ->assertJsonPath('data.signer_status', 'firmado');
});

it('responde 404 si el firmante no existe y no registra el evento', function () {
    $response = postWebhook(['event_id' => 'evt_x', 'signer_id' => '01a1ad1a-0000-7000-8000-000000000000']);

    assertProblem($response, 404, 'signer_not_found');
    expect(WebhookEvent::count())->toBe(0);
});

it('valida el payload', function (array $payload, string $field) {
    assertProblem(postWebhook($payload), 422, 'validation_failed')->assertJsonValidationErrors([$field]);
})->with([
    'sin event_id' => [['signer_id' => '01a1ad1a-0000-7000-8000-000000000000'], 'event_id'],
    'event_id con caracteres raros' => [['event_id' => 'evt <script>', 'signer_id' => '01a1ad1a-0000-7000-8000-000000000000'], 'event_id'],
    'signer_id no uuid' => [['event_id' => 'evt_1', 'signer_id' => '1 OR 1=1'], 'signer_id'],
]);

describe('autenticidad (HMAC)', function () {
    it('rechaza una firma inválida', function () {
        $response = postWebhook(['event_id' => 'evt_1', 'signer_id' => $this->signer->id], signature: 'sha256='.str_repeat('0', 64));

        assertProblem($response, 401, 'invalid_webhook_signature');
        expect($this->signer->fresh()->signed_at)->toBeNull();
    });

    it('rechaza un payload firmado con otro secreto', function () {
        assertProblem(
            postWebhook(['event_id' => 'evt_1', 'signer_id' => $this->signer->id], secret: 'secreto-de-un-atacante'),
            401,
            'invalid_webhook_signature',
        );
    });

    it('rechaza requests sin headers de firma', function () {
        $response = $this->postJson('/api/webhooks/signatures', ['event_id' => 'evt_1', 'signer_id' => $this->signer->id]);

        assertProblem($response, 401, 'invalid_webhook_signature');
    });

    it('rechaza timestamps fuera de la ventana (replay)', function (int $offset) {
        $response = postWebhook(['event_id' => 'evt_1', 'signer_id' => $this->signer->id], timestamp: time() + $offset);

        assertProblem($response, 401, 'invalid_webhook_signature');
    })->with(['10 minutos atrás' => -600, '10 minutos adelante' => 600]);

    it('rechaza todo si el secreto no está configurado (falla cerrado)', function () {
        config(['signing.webhook.secret' => '']);

        $response = postWebhook(['event_id' => 'evt_1', 'signer_id' => $this->signer->id], secret: '');

        assertProblem($response, 401, 'invalid_webhook_signature');
    });
});
