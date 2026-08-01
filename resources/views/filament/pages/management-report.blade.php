<x-filament-panels::page>
    @if (count($this->filters) > 0)
        {{ $this->filtersForm(new \Filament\Forms\Form($this)) }}
    @endif
    
    <div class="space-y-6">
        @if ($this->getHeaderWidgets())
            <x-filament-widgets::widgets
                :columns="$this->getHeaderWidgetsColumns()"
                :data="$this->getWidgetData()"
                :widgets="$this->getVisibleHeaderWidgets()"
            />
        @endif
        
        @if ($this->getFooterWidgets())
            <x-filament-widgets::widgets
                :columns="$this->getFooterWidgetsColumns()"
                :data="$this->getWidgetData()"
                :widgets="$this->getVisibleFooterWidgets()"
            />
        @endif
    </div>
</x-filament-panels::page>
