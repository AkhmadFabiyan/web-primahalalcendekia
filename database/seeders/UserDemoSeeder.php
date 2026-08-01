<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Enums\Role as RoleEnum;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        foreach (RoleEnum::cases() as $roleEnum) {
            $roleName = $roleEnum->value;
            // Generate email prefix from role name, e.g. "Super Admin" -> "super_admin"
            $emailPrefix = Str::slug($roleName, '_');
            $email = $emailPrefix . '@primahalalcendekia.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $roleName . ' Demo',
                    'password' => $password,
                    'status' => 'ACTIVE',
                    'email_verified_at' => now(),
                ]
            );

            // Assign role
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }
    }
}
