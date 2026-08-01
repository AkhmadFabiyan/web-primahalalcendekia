<?php

namespace App\Traits;

use App\Models\RecentlyViewedRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Pages\Page;

trait RecordsRecentlyViewed
{
    public function mountRecordsRecentlyViewed(): void
    {
        if (method_exists($this, 'getRecord')) {
            $record = $this->getRecord();
            if ($record instanceof Model) {
                $this->recordRecentlyViewed($record);
            }
        }
    }

    protected function recordRecentlyViewed(Model $record): void
    {
        $user = auth()->user();
        if (!$user) return;

        RecentlyViewedRecord::updateOrCreate(
            [
                'user_id' => $user->id,
                'record_type' => get_class($record),
                'record_id' => $record->getKey(),
            ],
            [
                'last_viewed_at' => now(),
            ]
        );

        // Limit to max 20 per user per type (or globally per user)
        $count = RecentlyViewedRecord::where('user_id', $user->id)->count();
        if ($count > 20) {
            $oldest = RecentlyViewedRecord::where('user_id', $user->id)
                ->orderBy('last_viewed_at', 'asc')
                ->limit($count - 20)
                ->get();
                
            foreach ($oldest as $item) {
                $item->delete();
            }
        }
    }
}
