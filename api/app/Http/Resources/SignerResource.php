<?php

namespace App\Http\Resources;

use App\Models\Signer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vista del firmante para el dueño del documento.
 * Nunca incluye token_hash ni IP.
 *
 * @mixin Signer
 */
class SignerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'rut' => $this->rut,
            'status' => $this->statusLabel(),
            'signed_at' => $this->signed_at?->toIso8601String(),
            'signed_via' => $this->signed_via?->value,
            'token_expires_at' => $this->token_expires_at->toIso8601String(),
        ];
    }
}
