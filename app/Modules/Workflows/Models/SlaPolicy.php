<?php

namespace App\Modules\Workflows\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Workflows\Enums\SlaDurationUnit;

class SlaPolicy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'duration_unit' => SlaDurationUnit::class,
        'uses_business_calendar' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'datetime',
    ];
}
