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

    protected static function booted()
    {
        static::saved(function ($model) {
            self::flushDashboardCache();
        });

        static::deleted(function ($model) {
            self::flushDashboardCache();
        });
    }

    protected static function flushDashboardCache()
    {
        // Flush wildcard cache or specific tags if Redis is used. 
        // For file/database cache without tags, we may have to clear the whole cache or use a specific prefix cleanup command.
        // Assuming there's a console command or we can just iterate, but Laravel Cache doesn't support wildcard out of the box unless using Redis.
        // So we will just clear the cache for simplicity if tagging isn't enabled, or run artisan cache:clear.
        // A better approach is to use a tagged cache if supported, or increment a cache version key.
        \Illuminate\Support\Facades\Artisan::call('cache:clear'); 
    }

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
