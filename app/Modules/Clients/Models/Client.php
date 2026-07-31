<?php

namespace App\Modules\Clients\Models;

use App\Models\User;
use App\Modules\Clients\Enums\ClientType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static function newFactory()
    {
        return \Database\Factories\ClientFactory::new();
    }

    protected $fillable = [
        'business_id',
        'client_type',
        'partner_id',
        'company_name',
        'company_type',
        'business_sector',
        'address',
        'city',
        'province',
        'postal_code',
        'pic_name',
        'pic_phone',
        'pic_email',
    ];

    protected $casts = [
        'client_type' => ClientType::class,
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function userAccount()
    {
        return $this->hasOne(User::class, 'client_id');
    }

    public function project()
    {
        return $this->hasOne(\App\Modules\Projects\Models\Project::class);
    }

    public function projectDocumentRequirements()
    {
        return $this->hasManyThrough(
            \App\Modules\Documents\Models\ProjectDocumentRequirement::class,
            \App\Modules\Projects\Models\Project::class,
            'client_id',
            'project_id',
            'id',
            'id'
        );
    }
}
