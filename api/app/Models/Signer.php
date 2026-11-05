<?php

namespace App\Models;

use App\Enums\SignedVia;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_id', 'email', 'rut', 'token_hash', 'token_expires_at',
        'signed_at', 'signed_via', 'signed_ip',
    ];

    protected $hidden = ['token_hash', 'signed_ip'];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
            'signed_at' => 'datetime',
            'signed_via' => SignedVia::class,
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->token_expires_at->isPast();
    }

    public function statusLabel(): string
    {
        return $this->isSigned() ? 'firmado' : 'pendiente';
    }
}
