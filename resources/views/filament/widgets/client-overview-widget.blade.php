<x-filament-widgets::widget>
    <x-filament::section>
        @if ($overview)
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-bold">{{ $overview['client_name'] }}</h2>
                        <p class="text-gray-500">{{ $overview['project_name'] }}</p>
                    </div>
                    <div>
                        <x-filament::badge color="primary">
                            {{ $overview['client_type'] }}
                        </x-filament::badge>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                    <x-filament::section compact>
                        <x-slot name="heading">Status Project</x-slot>
                        <p class="text-lg font-semibold">{{ $overview['status'] }}</p>
                    </x-filament::section>

                    <x-filament::section compact>
                        <x-slot name="heading">Progress Entry</x-slot>
                        <p class="text-lg font-semibold">{{ $overview['entry_progress'] }}%</p>
                    </x-filament::section>

                    <x-filament::section compact>
                        <x-slot name="heading">Progress Auditor</x-slot>
                        <p class="text-lg font-semibold">{{ $overview['auditor_progress'] }}%</p>
                    </x-filament::section>
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <x-heroicon-o-exclamation-circle class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Belum ada Project</h3>
                <p class="mt-1 text-sm text-gray-500">Saat ini Anda belum memiliki project yang aktif.</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
