<?php

namespace App\Modules\Clients\Services;

use App\Models\User;
use App\Modules\Clients\Models\Client;
use App\Enums\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ClientAccountService
{
    /**
     * Create login account for a client.
     *
     * @param Client $client
     * @return array
     * @throws \Exception
     */
    public function createAccount(Client $client): array
    {
        if ($client->userAccount()->exists()) {
            throw new \Exception('Klien ini sudah memiliki akun login.');
        }

        return DB::transaction(function () use ($client) {
            // Generate unique user identifier from business_id
            $cleanBusinessId = strtolower(str_replace('-', '', $client->business_id));
            $randomString = strtolower(Str::random(4));
            $username = "{$cleanBusinessId}_{$randomString}";
            
            $email = "{$username}@primahalalcendekia.com";

            // Pastikan email benar-benar unik
            while (User::where('email', $email)->exists()) {
                $randomString = strtolower(Str::random(4));
                $username = "{$cleanBusinessId}_{$randomString}";
                $email = "{$username}@primahalalcendekia.com";
            }

            $plainPassword = Str::random(12);

            $user = User::create([
                'name' => $client->company_name,
                'email' => $email,
                'phone' => $client->pic_phone,
                'password' => Hash::make($plainPassword), // Secure random password
                'client_id' => $client->id,
                'status' => 'ACTIVE', // You can change this to 'REQUIRE_PASSWORD_CHANGE' if needed
            ]);

            $user->assignRole(Role::KLIEN->value);

            // Logging via activity helper
            activity()
                ->performedOn($client)
                ->causedBy(auth()->user())
                ->event('created_account')
                ->log("Akun login Klien {$email} telah dibuat");

            return ['user' => $user, 'password' => $plainPassword];
        });
    }
}
