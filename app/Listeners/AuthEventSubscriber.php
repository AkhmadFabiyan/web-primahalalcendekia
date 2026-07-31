<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Request;

class AuthEventSubscriber
{
    /**
     * Handle user login events.
     */
    public function handleUserLogin(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;
        
        $user->last_login_at = now();
        $user->saveQuietly(); // Use saveQuietly to prevent "User Updated" activity log if tracked later

        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'guard' => $event->guard,
            ])
            ->log('Login berhasil');
    }

    /**
     * Handle user logout events.
     */
    public function handleUserLogout(Logout $event): void
    {
        if ($event->user) {
            activity('auth')
                ->causedBy($event->user)
                ->withProperties([
                    'ip' => Request::ip(),
                    'user_agent' => Request::userAgent(),
                    'guard' => $event->guard,
                ])
                ->log('Logout berhasil');
        }
    }

    /**
     * Handle user failed login events.
     */
    public function handleUserFailed(Failed $event): void
    {
        $properties = [
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'guard' => $event->guard,
            'email' => $event->credentials['email'] ?? null,
        ];

        $log = activity('auth')->withProperties($properties);
        
        if ($event->user) {
            $log->causedBy($event->user);
        }

        $log->log('Login gagal');
    }

    /**
     * Handle user lockout events.
     */
    public function handleUserLockout(Lockout $event): void
    {
        activity('auth')
            ->withProperties([
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'email' => collect($event->request->all())->get('email') ?? $event->request->input('data.email'),
            ])
            ->log('Lockout rate limit');
    }

    /**
     * Handle user password reset events.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ])
            ->log('Password reset berhasil');
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleUserLogin',
            Logout::class => 'handleUserLogout',
            Failed::class => 'handleUserFailed',
            Lockout::class => 'handleUserLockout',
            PasswordReset::class => 'handlePasswordReset',
        ];
    }
}
