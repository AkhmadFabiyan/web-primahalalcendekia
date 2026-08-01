<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model implements HasMedia
{
    use HasUuids, HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia, \App\Modules\Projects\Traits\LocksWhenProjectLocked;

    public function getProjectForLock()
    {
        return $this->invoice?->project;
    }

    protected $fillable = [
        'invoice_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'reference_number',
        'notes',
        'status',
        'verification_notes',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\PaymentFactory::new();
    }

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
