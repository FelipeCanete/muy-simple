<?php

namespace App\Services;

use App\Models\Signer;

final readonly class WebhookResult
{
    public function __construct(
        public Signer $signer,
        // El event_id ya se había procesado (reintento del proveedor).
        public bool $duplicateEvent,
        // El firmante ya estaba firmado antes de este evento.
        public bool $alreadySigned,
    ) {}
}
