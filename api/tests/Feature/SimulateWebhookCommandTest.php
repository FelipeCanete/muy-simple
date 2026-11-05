<?php

use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('envía al webhook un payload firmado correctamente', function () {
    Http::fake(['*' => Http::response(['data' => ['signer_status' => 'firmado']], 200)]);

    $this->artisan('webhook:simulate', ['signer' => 'abc-123', '--event' => 'evt_demo'])
        ->assertSuccessful();

    Http::assertSent(function (Request $request) {
        $expected = 'sha256='.VerifyWebhookSignature::sign(
            config('signing.webhook.secret'),
            $request->header('X-Timestamp')[0],
            $request->body(),
        );

        return $request->url() === config('signing.webhook.demo_url')
            && $request['event_id'] === 'evt_demo'
            && $request['signer_id'] === 'abc-123'
            && hash_equals($expected, $request->header('X-Signature')[0]);
    });
});

it('termina con error si la API rechaza el webhook', function () {
    Http::fake(['*' => Http::response(['code' => 'signer_not_found'], 404)]);

    $this->artisan('webhook:simulate', ['signer' => 'abc-123'])->assertFailed();
});
