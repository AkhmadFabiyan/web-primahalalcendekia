<div class="space-y-4">
    <div class="overflow-x-auto">
        <table class="w-full text-left divide-y table-auto">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">ID Klien</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Tahap</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Status Entry</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Status Pendamping</th>
                    <th class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 dark:bg-gray-800 dark:text-gray-400">Status Auditor</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($this->projects as $item)
                <tr>
                    <td class="px-4 py-2 text-sm">{{ $item->project->client_business_id }}</td>
                    <td class="px-4 py-2 text-sm">{{ $item->stage }}</td>
                    <td class="px-4 py-2 text-sm">
                        <select wire:change="updateProgress('{{ $item->project->id }}', 'ENTRY_PROGRESS', $event.target.value)" 
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                @if($item->is_completed) disabled @endif>
                            <option value="">-- Pilih --</option>
                            <option value="ENTRY_NOT_STARTED" @if($item->project->entry_status === 'ENTRY_NOT_STARTED') selected @endif>Belum Dikerjakan</option>
                            <option value="WAITING_CLIENT_DOCUMENTS" @if($item->project->entry_status === 'WAITING_CLIENT_DOCUMENTS') selected @endif>Menunggu Dokumen Klien</option>
                            <option value="ENTRY_COMPLETED" @if($item->project->entry_status === 'ENTRY_COMPLETED') selected @endif>Entry Selesai</option>
                        </select>
                    </td>
                    <td class="px-4 py-2 text-sm">
                        <select wire:change="updateProgress('{{ $item->project->id }}', 'COMPANION_PROGRESS', $event.target.value)" 
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                @if($item->is_completed) disabled @endif>
                            <option value="">-- Pilih --</option>
                            <option value="COMPANION_NOT_PROCESSED" @if($item->project->companion_status === 'COMPANION_NOT_PROCESSED') selected @endif>Belum Diproses</option>
                            <option value="WAITING_AUDIT_SCHEDULE" @if($item->project->companion_status === 'WAITING_AUDIT_SCHEDULE') selected @endif>Menunggu Jadwal Audit</option>
                            <option value="ASSISTANCE_COMPLETED" @if($item->project->companion_status === 'ASSISTANCE_COMPLETED') selected @endif>Pendampingan Selesai</option>
                        </select>
                    </td>
                    <td class="px-4 py-2 text-sm">
                        <select wire:change="updateProgress('{{ $item->project->id }}', 'AUDITOR_PROGRESS', $event.target.value)" 
                                class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-700"
                                @if($item->is_completed) disabled @endif>
                            <option value="">-- Pilih --</option>
                            <option value="AUDITOR_NOT_PROCESSED" @if($item->project->auditor_status === 'AUDITOR_NOT_PROCESSED') selected @endif>Belum Diproses</option>
                            <option value="DOCUMENT_REVIEW" @if($item->project->auditor_status === 'DOCUMENT_REVIEW') selected @endif>Pemeriksaan Dokumen</option>
                            <option value="HALAL_CERTIFICATE_ISSUED" @if($item->project->auditor_status === 'HALAL_CERTIFICATE_ISSUED') selected @endif>Sertifikat Halal Terbit</option>
                        </select>
                    </td>
                </tr>
                @endforeach
                @if($this->projects->isEmpty())
                <tr>
                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">Tidak ada data.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
