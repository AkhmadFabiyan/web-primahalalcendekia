<?php

namespace App\Modules\Leads\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Modules\Clients\Enums\ClientType;
use App\Modules\Leads\Enums\LeadStatus;
use App\Modules\Leads\Enums\PaymentScheme;
use App\Modules\Clients\Models\Partner;
use App\Models\User;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Illuminate\Database\Eloquent\Builder;

class Lead extends Model
{
    use HasUuids, LogsActivity, \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\LeadFactory::new();
    }

    protected $casts = [
        'client_type' => ClientType::class,
        'status' => LeadStatus::class,
        'payment_scheme' => PaymentScheme::class,
        'client_nominal' => 'decimal:2',
        'partner_nominal' => 'decimal:2',
        'installment_count' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted()
    {
        static::saving(function ($lead) {
            if ($lead->pic_email) {
                $lead->pic_email = strtolower($lead->pic_email);
            }
            if ($lead->partner_email) {
                $lead->partner_email = strtolower($lead->partner_email);
            }
        });
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Scope a query to only include leads owned by a specific marketing user.
     */
    public function scopeOwnedByMarketing(Builder $query, $userId): Builder
    {
        return $query->where('marketing_id', $userId);
    }

    public function project()
    {
        return $this->hasOne(\App\Modules\Projects\Models\Project::class, 'source_lead_id');
    }
}
