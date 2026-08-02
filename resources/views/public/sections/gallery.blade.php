<section class="py-16 bg-phc-surface overflow-hidden" aria-labelledby="gallery-heading">
    <div class="public-container">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-12" data-reveal>
            <div class="max-w-2xl">
                <h2 id="gallery-heading" class="text-3xl md:text-4xl font-bold mb-4">Dokumentasi Pendampingan</h2>
                <p class="text-lg text-phc-muted">Momen saat tim kami mendampingi klien di lapangan untuk memastikan standar sertifikasi terpenuhi dengan baik.</p>
            </div>
            
            <div class="hidden md:flex gap-2">
                <button type="button" class="w-10 h-10 rounded-full border border-phc-border flex items-center justify-center text-phc-primary hover:bg-phc-primary hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Sebelumnya" data-gallery-prev>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </button>
                <button type="button" class="w-10 h-10 rounded-full border border-phc-border flex items-center justify-center text-phc-primary hover:bg-phc-primary hover:text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed" aria-label="Selanjutnya" data-gallery-next>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </button>
            </div>
        </div>

        <div class="gallery-viewport overflow-hidden" data-gallery-viewport data-reveal>
            <div class="gallery-container flex -ml-4">
                @for($i = 1; $i <= 6; $i++)
                <figure class="gallery-slide flex-[0_0_100%] sm:flex-[0_0_50%] lg:flex-[0_0_33.333333%] pl-4">
                    <div class="group relative rounded-2xl overflow-hidden aspect-[4/3] bg-phc-border h-full">
                        <x-public.image 
                            path="images/gallery/placeholder-{{ $i }}.jpg" 
                            alt="Dokumentasi kegiatan pendampingan klien {{ $i }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                            width="400"
                            height="300"
                            loading="lazy"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <figcaption class="absolute bottom-0 left-0 w-full p-6 text-white translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-300 pointer-events-none">
                            <p class="font-bold">Pendampingan Klien {{ $i }}</p>
                            <p class="text-sm text-white/80">Proses Audit Eksternal</p>
                        </figcaption>
                    </div>
                </figure>
                @endfor
            </div>
        </div>
    </div>
</section>
