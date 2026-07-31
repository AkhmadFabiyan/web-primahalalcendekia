<?php

namespace App\Filament\Pages\Auth;

class Login extends \Filament\Auth\Pages\Login
{
    /**
     * Get the credentials from the form data to be used in authentication.
     * We add status => ACTIVE so that attempt() natively fails for inactive users
     * before firing any Login success events.
     * 
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
            'status' => 'ACTIVE',
        ];
    }
}
