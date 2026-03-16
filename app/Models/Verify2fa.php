<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verify2fa extends Model
{
    use HasFactory;

    protected $table = 'verify2fas';

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'enables_2fa',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'token' => 'hashed',
        'enables_2fa' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
