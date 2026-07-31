<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Super Admin account interactively';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating a new Super Admin account...');

        $name = $this->ask('Name');
        
        $email = '';
        while (empty($email)) {
            $emailInput = $this->ask('Email Address');
            $validator = Validator::make(['email' => $emailInput], [
                'email' => ['required', 'email', 'unique:users,email']
            ]);

            if ($validator->fails()) {
                $this->error($validator->errors()->first('email'));
            } else {
                $email = $emailInput;
            }
        }

        $password = '';
        while (empty($password)) {
            $passwordInput = $this->secret('Password (min 8 characters)');
            $validator = Validator::make(['password' => $passwordInput], [
                'password' => ['required', 'min:8']
            ]);

            if ($validator->fails()) {
                $this->error($validator->errors()->first('password'));
            } else {
                $password = $passwordInput;
            }
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'status' => 'ACTIVE',
        ]);

        $user->syncRoles([Role::SUPER_ADMIN->value]);

        $this->info("Super Admin account for {$name} created successfully!");
    }
}
