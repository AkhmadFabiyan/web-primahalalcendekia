<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateCompanySettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.company_name', 'Prima Halal Cendekia');
        $this->migrator->add('company.address', 'Jl. Contoh Alamat No. 123');
        $this->migrator->add('company.phone', '0211234567');
        $this->migrator->add('company.email', 'info@primahalalcendekia.com');
    }
}
