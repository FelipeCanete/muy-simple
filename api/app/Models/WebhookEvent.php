<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['event_id', 'signer_id', 'received_at'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }
}
