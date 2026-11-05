<?php

namespace App\Http\Resources;

use App\Models\Signer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lo que ve un firmante al abrir su enlace: solo su propio estado y
 * datos básicos del documento (no la lista de otros firmantes).
 *
 * @mixin Signer
 */
class SigningPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $document = $this->document;

        return [
            'document' => [
                'title' => $document->title,
                'original_name' => $document->original_name,
                'size' => $document->size,
                'sha256' => $document->sha256,
                'status' => $document->status->value,
                'status_label' => $document->status->label(),
            ],
            'signer' => [
                'email' => $this->email,
                'status' => $this->statusLabel(),
                'signed_at' => $this->signed_at?->toIso8601String(),
                'expires_at' => $this->token_expires_at->toIso8601String(),
            ],
        ];
    }
}
