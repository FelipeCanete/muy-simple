<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $signers = $this->whenLoaded('signers');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'original_name' => $this->original_name,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'signers_total' => $this->signers_total ?? ($this->relationLoaded('signers') ? $this->signers->count() : null),
            'signers_signed' => $this->signers_signed ?? ($this->relationLoaded('signers') ? $this->signers->whereNotNull('signed_at')->count() : null),
            'signers' => SignerResource::collection($signers),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
