<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebQrSession extends Model
{
    use HasFactory;

    protected $table = 'web_qr_sessions';

    protected $fillable = [
        'session_id',
        'user_id',
        'status',
        'confirmed_at',
        'expires_at',
    ];

    protected $dates = [
        'confirmed_at',
        'expires_at',
        'created_at',
        'updated_at'
    ];
    public function isExpired(): bool
    {
        return $this->created_at->lt(now()->subMinutes(2));
    }
}
