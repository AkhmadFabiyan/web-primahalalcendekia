<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Modules\Payments\Enums\ReceiptType;

class Receipt extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'receipt_type' => ReceiptType::class,
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'snapshot' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
