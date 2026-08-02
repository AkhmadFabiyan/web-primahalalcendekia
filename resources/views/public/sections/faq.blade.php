<section id="faq" class="py-16 bg-white" aria-labelledby="faq-heading">
    <div class="public-container narrow">
        <div class="text-center mb-12" data-reveal>
            <h2 id="faq-heading" class="text-3xl md:text-4xl font-bold mb-4">Pertanyaan yang Sering Diajukan</h2>
            <p class="text-lg text-phc-muted">Temukan jawaban atas kebingungan Anda seputar proses sertifikasi Halal dan ISO.</p>
        </div>

        <div class="space-y-4" data-reveal>
            @php
                $faqs = [
                    [
                        'q' => 'Apakah dokumen dan SOP harus siap sebelum proses dimulai?',
                        'a' => 'Tidak perlu. Salah satu nilai tambah kami adalah membantu Anda menyiapkan, menyusun, dan memperbaiki dokumen serta Standar Operasional Prosedur (SOP) sesuai persyaratan yang ditetapkan oleh lembaga sertifikasi.'
                    ],
                    [
                        'q' => 'Berapa lama estimasi proses sertifikasi hingga terbit?',
                        'a' => 'Estimasi waktu bervariasi bergantung pada skala bisnis, kelengkapan dokumen awal, dan antrean di badan sertifikasi (seperti BPJPH untuk Halal). Namun, dengan pendampingan PHC, proses ini kami pastikan berjalan seefisien dan secepat mungkin karena minim revisi.'
                    ],
                    [
                        'q' => 'Apakah PHC mendampingi saat proses audit berlangsung?',
                        'a' => 'Tentu saja. Kami tidak akan meninggalkan Anda saat audit. Konsultan kami akan melakukan pra-audit (simulasi) terlebih dahulu dan siap mendampingi tim Anda saat auditor eksternal melakukan pemeriksaan di lapangan.'
                    ],
                    [
                        'q' => 'Bagaimana dengan kerahasiaan data perusahaan (resep, bahan, dll)?',
                        'a' => 'Kerahasiaan data klien adalah prioritas tertinggi kami. Sebelum proses pendampingan dimulai, kami selalu menandatangani Perjanjian Kerahasiaan (Non-Disclosure Agreement / NDA) yang mengikat secara hukum demi melindungi aset intelektual Anda.'
                    ]
                ];
            @endphp

            @foreach($faqs as $index => $faq)
                <div class="group bg-phc-surface border border-phc-border rounded-xl overflow-hidden" data-accordion-item>
                    <button 
                        type="button" 
                        class="w-full flex justify-between items-center font-bold text-left cursor-pointer p-6 text-lg transition-colors hover:text-phc-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-phc-primary"
                        aria-expanded="false" 
                        aria-controls="faq-content-{{ $index }}"
                        data-accordion-trigger
                    >
                        <span>{{ $faq['q'] }}</span>
                        <span class="transition-transform duration-300" data-accordion-icon>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </span>
                    </button>
                    <div 
                        id="faq-content-{{ $index }}" 
                        class="grid transition-all duration-300 grid-rows-[0fr]"
                        data-accordion-panel
                        aria-hidden="true"
                    >
                        <div class="overflow-hidden">
                            <div class="text-phc-muted px-6 pb-6 pt-0 border-t border-phc-border/50">
                                <p class="pt-4">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
