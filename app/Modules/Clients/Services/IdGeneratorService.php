<?php

namespace App\Modules\Clients\Services;

use Illuminate\Support\Facades\DB;

class IdGeneratorService
{
    /**
     * Generate ID Klien dengan format PHC-HAL-YYYY-XXXX
     *
     * @return string
     */
    public function generateClientId(): string
    {
        $year = date('Y');
        $sequenceName = "client_id_{$year}";

        $nextValue = $this->getNextSequenceValue($sequenceName);

        // Format: PHC-HAL-YYYY-XXXX
        return sprintf('PHC-HAL-%s-%04d', $year, $nextValue);
    }

    /**
     * Generate Partner Code dengan format PARTNER-YYYY-XXXX
     *
     * @return string
     */
    public function generatePartnerCode(): string
    {
        $year = date('Y');
        $sequenceName = "partner_id_{$year}";

        $nextValue = $this->getNextSequenceValue($sequenceName);

        // Format: PARTNER-YYYY-XXXX
        return sprintf('PARTNER-%s-%04d', $year, $nextValue);
    }

    /**
     * Get next sequence value securely against race conditions.
     */
    private function getNextSequenceValue(string $sequenceName): int
    {
        return DB::transaction(function () use ($sequenceName) {
            DB::table('sequences')->insertOrIgnore([
                'name' => $sequenceName,
                'value' => 0,
            ]);

            $sequence = DB::table('sequences')
                ->where('name', $sequenceName)
                ->lockForUpdate()
                ->first();

            $nextValue = $sequence->value + 1;

            DB::table('sequences')
                ->where('name', $sequenceName)
                ->update(['value' => $nextValue]);

            return $nextValue;
        });
    }
}
