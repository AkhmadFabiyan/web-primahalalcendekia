<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class ApiConsumer extends Model
{
    use HasApiTokens, HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'client_id',
        'partner_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
