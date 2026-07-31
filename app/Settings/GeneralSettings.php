<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $display_timezone;
    public string $locale;
    public string $date_format;

    public static function group(): string
    {
        return 'general';
    }
}
