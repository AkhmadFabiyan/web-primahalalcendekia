<?php

namespace App\Modules\Payments\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentSequenceService
{
    /**
     * Generate next payment number atomically.
     * Format: PAY/YYYY/XXXXXX
     * Example: PAY/2026/000001
     */
    public function generateNextNumber(): string
    {
        $year = Carbon::now()->year;
        $type = 'PAY_' . $year;

        return DB::transaction(function () use ($year, $type) {
            $sequence = DB::table('sequences')
                ->where('name', $type)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                $nextId = $sequence->value + 1;
                DB::table('sequences')
                    ->where('name', $type)
                    ->update(['value' => $nextId]);
            } else {
                $nextId = 1;
                DB::table('sequences')->insert([
                    'name' => $type,
                    'value' => $nextId,
                ]);
            }

            $formattedId = str_pad($nextId, 6, '0', STR_PAD_LEFT);
            return "PAY/{$year}/{$formattedId}";
        });
    }
}
