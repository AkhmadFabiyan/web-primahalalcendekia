<?php

namespace App\Modules\Payments\Services;

use Illuminate\Support\Facades\DB;

class ReceiptSequenceService
{
    public function generateNextNumber(): string
    {
        $year = date('Y');
        $sequenceName = "receipt_id_{$year}";

        $nextValue = DB::transaction(function () use ($sequenceName) {
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

        return sprintf('RCT/PHC/%s/%06d', $year, $nextValue);
    }
}
