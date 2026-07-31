<?php

namespace App\Modules\Clients\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Partner extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected static function newFactory()
    {
        return \Database\Factories\PartnerFactory::new();
    }

    protected $fillable = [
        'partner_code',
        'name',
        'pic_name',
        'phone',
        'email',
        'address',
    ];

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
    public function invoices()
    {
        return $this->hasMany(\App\Modules\Payments\Models\Invoice::class);
    }
}
