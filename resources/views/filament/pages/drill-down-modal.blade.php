<div>
    @if (in_array($arguments['type'] ?? 'kpi', ['invoice', 'payment']))
        @livewire(\App\Livewire\FinanceDrillDownContent::class, [
            'type' => $arguments['type'] ?? 'invoice',
            'key' => $arguments['key'] ?? '',
            'filters' => \App\Modules\Dashboards\DataTransferObjects\FinanceDashboardFilterData::fromArray($this->filters)->toArray(),
        ])
    @else
        @livewire(\App\Livewire\DrillDownContent::class, [
            'type' => $arguments['type'] ?? 'kpi',
            'key' => $arguments['key'] ?? '',
            'filters' => \App\Modules\Dashboards\DataTransferObjects\OperationalDashboardFilterData::fromArray($this->filters)->toArray(),
        ])
    @endif
</div>
