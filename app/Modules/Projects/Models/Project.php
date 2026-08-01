<?php

namespace App\Modules\Projects\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use App\Modules\Clients\Models\Client;
use App\Modules\Leads\Models\Lead;
use App\Modules\Projects\Enums\ProjectStatus;
use App\Modules\Leads\Enums\PaymentScheme;

class Project extends Model
{
    use HasUuids, HasFactory, SoftDeletes, LogsActivity, \App\Modules\Projects\Traits\LocksWhenProjectLocked;

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

    public function projectDocumentRequirements(): HasMany
    {
        return $this->hasMany(\App\Modules\Documents\Models\ProjectDocumentRequirement::class);
    }

    public function sihalalCredential(): HasOne
    {
        return $this->hasOne(SihalalCredential::class);
    }

    public function auditPlan(): HasOne
    {
        return $this->hasOne(\App\Modules\Workflows\Models\AuditPlan::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(ProjectPaymentSchedule::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(\App\Modules\Payments\Models\Invoice::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [ProjectStatus::COMPLETED, ProjectStatus::CANCELLED]);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Modules\Workflows\Models\Task::class);
    }

    public function archives(): HasMany
    {
        return $this->hasMany(ProjectArchive::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(\App\Modules\Documents\Models\Document::class);
    }
}
