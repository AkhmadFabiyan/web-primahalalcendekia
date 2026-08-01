<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Livewire\Attributes\Url;

class Dashboard extends BaseDashboard
{
    /**
     * @var string
     */
    #[Url]
    public $section = 'overview';

    /**
     * The valid sections for Client Dashboard.
     */
    protected array $validClientSections = [
        'overview', 'progress', 'payments', 'documents', 'certificate', 'timeline'
    ];

    public function mount()
    {
        // Enforce safe section values for Client Dashboard
        if (auth()->user()->isClient()) {
            if (!in_array($this->section, $this->validClientSections)) {
                $this->section = 'overview';
            }
        }
    }

    /**
     * Determine the widgets that should be available on the dashboard.
     *
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public function getWidgets(): array
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return [
                \App\Filament\Widgets\SystemHealthWidget::class,
                \App\Filament\Widgets\UserStatsWidget::class,
                \App\Filament\Widgets\LatestActivityLogWidget::class,
            ];
        }

        if ($user->isClient()) {
            return [
                \App\Filament\Widgets\ClientOverviewWidget::class,
                \App\Filament\Widgets\ClientInvoicesWidget::class,
                \App\Filament\Widgets\ClientDocumentsWidget::class,
                \App\Filament\Widgets\ClientTimelineWidget::class,
            ];
        }

        if ($user->isInternalStaff()) {
            return [
                \App\Filament\Widgets\PersonalWorkloadWidget::class,
                \App\Filament\Widgets\MyTasksWidget::class,
            ];
        }

        return [];
    }
}
