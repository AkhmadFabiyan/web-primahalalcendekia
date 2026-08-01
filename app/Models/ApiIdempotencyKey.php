<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiIdempotencyKey extends Model
{
    protected $fillable = [
        'api_consumer_id',
        'method',
        'endpoint',
        'idempotency_key',
        'request_hash',
        'response_status',
        'response_body',
        'resource_type',
        'resource_id',
        'expires_at',
    ];

    protected $casts = [
        'response_body' => 'array',
        'expires_at' => 'datetime',
    ];
}
