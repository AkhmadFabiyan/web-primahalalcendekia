<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white">
                Daftar Prioritas Tindak Lanjut
            </h2>
        </div>
        
        @php
            $projects = $this->getPriorityData();
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full text-left divide-y table-auto xl:whitespace-nowrap">
                <thead>
                    <tr>
                        <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">ID Klien</th>
                        <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Tahap</th>
                        <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Status</th>
                        <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Kondisi</th>
                        <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Update Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y whitespace-nowrap">
                    @forelse($projects as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item->project->client_business_id }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item->stage }}</td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                            <div>Entry: {{ $item->project->entry_status ?? '-' }}</div>
                            <div>Audit: {{ $item->project->auditor_status ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            @if($item->is_critical)
                                <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">Kritis</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold text-amber-800 bg-amber-100 rounded-full dark:bg-amber-900 dark:text-amber-200">Follow Up</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item->last_progress_date->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">Tidak ada project yang memerlukan tindak lanjut prioritas saat ini.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
