<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class OperationalGuideWidget extends Widget
{
    protected string $view = 'filament.widgets.operational-guide-widget';
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->isInternalStaff() && !$user->isSuperAdmin();
    }
}
