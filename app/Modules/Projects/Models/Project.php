<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use App\Modules\Clients\Models\Client;
use App\Modules\Leads\Models\Lead;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Leads\Enums\PaymentScheme;

class Project extends Model
{
    use HasUuids, HasFactory, SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\ProjectFactory::new();
    }

    protected function casts(): array
    {
        return [
            'client_nominal' => 'decimal:2',
            'partner_nominal' => 'decimal:2',
            'installment_count' => 'integer',
            'payment_scheme' => PaymentScheme::class,
            'status' => ProjectStatus::class,
            'activated_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sourceLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'source_lead_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }
}
