<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white">
                Ringkasan Tahap
            </h2>
        </div>
        
        @php
            $data = $this->getDistributionData();
        @endphp

        <table class="w-full text-left divide-y table-auto xl:whitespace-nowrap">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Tahap</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y whitespace-nowrap">
                @foreach($data as $stage => $count)
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $stage }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $count }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </x-filament::section>
</x-filament-widgets::widget>
