<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'created_by', 'title', 'original_name', 'stored_path',
        'mime', 'size', 'sha256', 'status',
    ];

    protected $hidden = ['stored_path'];

    protected $attributes = [
        'status' => 'pendiente',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
            'size' => 'integer',
        ];
    }

    public function signers(): HasMany
    {
        return $this->hasMany(Signer::class)->orderBy('created_at')->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Un usuario solo ve los documentos que él mismo creó (OWASP A01). */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('created_by', $user->getKey());
    }

    public function scopeWithSignatureCounts(Builder $query): Builder
    {
        return $query->withCount([
            'signers as signers_total',
            'signers as signers_signed' => fn (Builder $q) => $q->whereNotNull('signed_at'),
        ]);
    }
}
