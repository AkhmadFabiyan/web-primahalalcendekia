<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'password', 'status', 'client_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles, \Spatie\Activitylog\Models\Concerns\LogsActivity;

    public function getActivitylogOptions(): \Spatie\Activitylog\Support\LogOptions
    {
        return \Spatie\Activitylog\Support\LogOptions::defaults()
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'string',
        ];
    }

    public static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('status') && $user->status === 'INACTIVE') {
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(\App\Enums\Role::SUPER_ADMIN->value);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_assignments');
    }

    /**
     * Get the entity's notifications.
     */
    public function notifications(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Modules\Notifications\Models\DatabaseNotification::class, 'notifiable')->latest();
    }

    /**
     * Get the entity's read notifications.
     */
    public function readNotifications(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->notifications()->whereNotNull('read_at');
    }

    /**
     * Get the entity's unread notifications.
     */
    public function unreadNotifications(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->notifications()->whereNull('read_at');
    }

    public function client()
    {
        return $this->belongsTo(\App\Modules\Clients\Models\Client::class);
    }

    public function isClient(): bool
    {
        return $this->hasRole(\App\Enums\Role::KLIEN->value);
    }

    public function isInternalStaff(): bool
    {
        return ! $this->isClient();
    }
}
