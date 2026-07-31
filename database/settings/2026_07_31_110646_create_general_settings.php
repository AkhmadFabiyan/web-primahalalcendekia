<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class CreateGeneralSettings extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.display_timezone', 'Asia/Jakarta');
        $this->migrator->add('general.locale', 'id');
        $this->migrator->add('general.date_format', 'd M Y');
    }
}
