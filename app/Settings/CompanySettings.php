<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class CompanySettings extends Settings
{
    public string $company_name;
    public string $address;
    public string $phone;
    public string $email;

    public static function group(): string
    {
        return 'company';
    }
}
