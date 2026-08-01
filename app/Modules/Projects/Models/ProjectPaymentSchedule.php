<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProjectPaymentSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'project_id',
        'sequence',
        'invoice_type',
        'client_amount',
        'partner_amount',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
