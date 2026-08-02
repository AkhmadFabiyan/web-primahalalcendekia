<?php

namespace App\Filament\Widgets;

use App\Modules\Dashboards\Services\ClientDashboardService;
use Filament\Widgets\Widget;

class ClientOverviewWidget extends Widget
{
    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.client-overview-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        if (! auth()->user()->isClient()) {
            return false;
        }

        $section = request('section', 'overview');

        return $section === 'overview' || $section === 'progress';
    }

    protected function getViewData(): array
    {
        $service = app(ClientDashboardService::class);
        $overview = $service->getProjectOverview(auth()->user());

        return [
            'overview' => $overview,
        ];
    }
}
