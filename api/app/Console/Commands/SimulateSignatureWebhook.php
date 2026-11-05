<?php

namespace App\Console\Commands;

use App\Http\Middleware\VerifyWebhookSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Herramienta de demo: se comporta como el proveedor externo y llama
 * al webhook con un payload firmado con WEBHOOK_SECRET.
 */
class SimulateSignatureWebhook extends Command
{
    protected $signature = 'webhook:simulate
        {signer : ID (uuid) del firmante a marcar como firmado}
        {--event= : event_id a enviar (por defecto uno aleatorio; repítelo para probar idempotencia)}';

    protected $description = 'Simula el callback del proveedor de firma para un firmante';

    public function handle(): int
    {
        $secret = (string) config('signing.webhook.secret');

        if ($secret === '') {
            $this->error('WEBHOOK_SECRET no está configurado.');

            return self::FAILURE;
        }

        $body = json_encode([
            'event_id' => $this->option('event') ?: 'evt_'.Str::lower(Str::ulid()),
            'signer_id' => $this->argument('signer'),
        ], JSON_THROW_ON_ERROR);

        $timestamp = (string) time();

        $response = Http::withHeaders([
            'X-Timestamp' => $timestamp,
            'X-Signature' => 'sha256='.VerifyWebhookSignature::sign($secret, $timestamp, $body),
            'Accept' => 'application/json',
        ])
            ->withBody($body, 'application/json')
            ->timeout(10)
            ->post(config('signing.webhook.demo_url'));

        $this->line("HTTP {$response->status()}");
        $this->line(json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $response->successful() ? self::SUCCESS : self::FAILURE;
    }
}
