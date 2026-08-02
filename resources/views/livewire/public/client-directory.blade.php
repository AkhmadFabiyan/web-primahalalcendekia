<div>
    <section id="direktori" class="py-16 bg-white" aria-labelledby="directory-heading">
        <div class="public-container">
            <h2 id="directory-heading" class="sr-only">Direktori Portofolio Klien</h2>
    
            <!-- Search and Filter Form -->
            <div class="mb-10 bg-phc-surface p-6 rounded-2xl border border-phc-border">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1 relative">
                        <label for="q" class="sr-only">Cari Nama atau Nomor</label>
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-phc-muted">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <input 
                            type="search" 
                            id="q"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Cari nama perusahaan atau nomor identitas..."
                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-phc-border bg-white focus:outline-none focus:ring-2 focus:ring-phc-primary/50 focus:border-phc-primary transition-colors"
                        >
                    </div>
                    
                    <div class="md:w-64">
                        <label for="sector" class="sr-only">Pilih Bidang Usaha</label>
                        <select 
                            id="sector"
                            wire:model.live="sector"
                            class="w-full px-4 py-3 rounded-xl border border-phc-border bg-white focus:outline-none focus:ring-2 focus:ring-phc-primary/50 focus:border-phc-primary appearance-none cursor-pointer"
                        >
                            <option value="">Semua Bidang Usaha</option>
                            @foreach($sectors as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                @if($search || $sector)
                    <div class="mt-4 pt-4 border-t border-phc-border flex items-center justify-between">
                        <p class="text-sm text-phc-muted">
                            Menampilkan hasil untuk 
                            @if($search) pencarian "<strong>{{ $search }}</strong>" @endif
                            @if($search && $sector) dan @endif
                            @if($sector) bidang usaha "<strong>{{ $sector }}</strong>" @endif
                        </p>
                        <button type="button" wire:click="clearFilters" class="text-sm text-phc-primary hover:underline">Hapus Filter</button>
                    </div>
                @endif
            </div>
    
            <!-- Data Presentation -->
            @if(!$hasData)
                <!-- Global Empty State -->
                <div class="text-center py-20 bg-phc-surface rounded-2xl border border-phc-border">
                    <div class="inline-flex w-16 h-16 bg-white rounded-full items-center justify-center text-phc-muted mb-4 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Digitalisasi Data Klien</h3>
                    <p class="text-phc-muted max-w-xl mx-auto">Ratusan pelaku usaha telah mempercayakan sertifikasinya bersama Prima Halal Cendekia. Saat ini, tim kami sedang dalam tahap memasukkan (migrasi) data historis klien-klien kami ke dalam sistem baru ini agar dapat Anda lihat secara lengkap. Silakan kembali lagi nanti!</p>
                </div>
            @elseif($clients->isEmpty())
                <!-- No Result State -->
                <div class="text-center py-20 bg-white border border-phc-border rounded-2xl border-dashed">
                    <div class="inline-flex w-16 h-16 bg-phc-surface rounded-full items-center justify-center text-phc-muted mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Pencarian Tidak Ditemukan</h3>
                    <p class="text-phc-muted max-w-md mx-auto">Tidak ada portofolio yang sesuai dengan kriteria pencarian Anda. Coba kata kunci atau bidang usaha lain.</p>
                    <button type="button" wire:click="clearFilters" class="inline-block mt-6 button button-secondary">Lihat Semua Klien</button>
                </div>
            @else
                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto rounded-2xl border border-phc-border bg-white shadow-sm mb-8">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-phc-surface border-b border-phc-border text-sm text-gray-600 uppercase tracking-wider">
                                <th class="py-4 px-6 font-semibold w-24">No.</th>
                                <th class="py-4 px-6 font-semibold">ID Sertifikat Halal (Nomor Sertifikat Halal)</th>
                                <th class="py-4 px-6 font-semibold">Nama Klien / Perusahaan</th>
                                <th class="py-4 px-6 font-semibold">Bidang Usaha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-phc-border">
                            @foreach($clients as $index => $client)
                                <tr class="hover:bg-phc-surface/50 transition-colors">
                                    <td class="py-4 px-6 text-gray-500">
                                        {{ $clients->firstItem() + $index }}
                                    </td>
                                    <td class="py-4 px-6 font-mono text-sm">
                                        <span class="px-2 py-1 bg-gray-100 rounded-md border border-gray-200 text-gray-600">
                                            {{ $client->project?->certificate?->certificate_number ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-gray-900">
                                        {{ $client->company_name }}
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-phc-primary/10 text-phc-primary">
                                            {{ $client->business_sector }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
    
                <!-- Mobile Card View -->
                <div class="md:hidden grid grid-cols-1 gap-4 mb-8">
                    @foreach($clients as $index => $client)
                        <div class="bg-white p-5 rounded-2xl border border-phc-border shadow-sm">
                            <div class="flex justify-between items-start mb-3">
                                <span class="px-2 py-1 bg-gray-100 rounded-md border border-gray-200 text-gray-600 font-mono text-xs">
                                    {{ $client->project?->certificate?->certificate_number ?? '-' }}
                                </span>
                                <span class="text-xs text-gray-400 font-mono">#{{ $clients->firstItem() + $index }}</span>
                            </div>
                            <h3 class="font-bold text-lg mb-2 text-gray-900 leading-tight">{{ $client->company_name }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-phc-primary/10 text-phc-primary">
                                {{ $client->business_sector }}
                            </span>
                        </div>
                    @endforeach
                </div>
    
                <!-- Pagination -->
                @if($clients->hasPages())
                    <div class="mt-8">
                        {{ $clients->links('pagination::tailwind') }}
                    </div>
                @endif
            @endif
        </div>
    </section>
</div>
