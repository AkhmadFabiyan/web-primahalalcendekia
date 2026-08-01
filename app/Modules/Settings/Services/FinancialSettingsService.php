<?php

namespace App\Modules\Settings\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FinancialSettingsService
{
    private $cacheKey = 'financial_settings';
    private $cacheTtl = 86400; // 24 hours

    public function getAllSettings(): array
    {
        return Cache::remember($this->cacheKey, $this->cacheTtl, function () {
            // Kita akan asumsikan settings group 'financial'
            $settings = DB::table('settings')->where('group', 'financial')->get();
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->name] = json_decode($setting->payload, true);
            }
            return $result;
        });
    }

    public function get(string $key, $default = null)
    {
        $settings = $this->getAllSettings();
        return $settings[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['group' => 'financial', 'name' => $key],
            ['payload' => json_encode($value), 'updated_at' => now()]
        );
        Cache::forget($this->cacheKey);
    }
}
