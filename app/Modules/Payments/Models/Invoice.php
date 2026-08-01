<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use App\Modules\Projects\Models\Project;
use App\Modules\Clients\Models\Partner;
use App\Modules\Payments\Enums\InvoiceType;
use App\Modules\Payments\Enums\InvoiceAudience;
use App\Modules\Payments\Enums\InvoiceStatus;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Invoice extends Model implements HasMedia
{
    use HasUuids, HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia, \App\Modules\Projects\Traits\LocksWhenProjectLocked;

    protected static function booted()
    {
        static::saved(function ($model) {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
        });

        static::deleted(function ($model) {
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
        });
    }

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\InvoiceFactory::new();
    }

    protected function casts(): array
    {
        return [
            'invoice_type' => InvoiceType::class,
            'audience' => InvoiceAudience::class,
            'status' => InvoiceStatus::class,
            'billing_snapshot' => 'array',
            'sequence' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'total' => 'decimal:2',
            'issued_at' => 'datetime',
            'due_date' => 'datetime',
            'published_at' => 'datetime',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('government-invoice-document')
            ->acceptsMimeTypes(['application/pdf'])
            ->useDisk('local') // private disk
            ->singleFile();
    }
}
