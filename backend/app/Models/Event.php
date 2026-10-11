<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'datetime'];
    }

    /** Usuario que provocó el evento (null si era un invitado). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
