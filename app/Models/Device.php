<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'device_id', 'device_name', 'platform', 'active_profile', 'session_id', 'last_activity'];

    protected $casts = [
        'user_id' => 'integer',
        'last_activity' => 'datetime',
    ];

}
