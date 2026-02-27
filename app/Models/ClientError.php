<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientError extends Model
{
    use HasFactory;

    protected $fillable = [
        'error_type',
        'message',
        'stack',
        'url',
        'user_agent',
        'manager_id',
        'extra_data',
    ];

    protected $casts = [
        'extra_data' => 'array',
    ];
}
