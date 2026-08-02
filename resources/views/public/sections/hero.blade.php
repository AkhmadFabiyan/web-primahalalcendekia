<section id="beranda" class="hero-section" aria-label="Beranda Utama">
    <div class="public-container hero-grid">
        <div class="hero-copy" data-reveal data-reveal-direction="right">
            <span class="inline-flex items-center gap-2 px-3 py-1 mb-6 text-sm font-bold text-phc-primary bg-phc-primary/10 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                Solusi Tuntas Sertifikasi
            </span>
            
            <h1>Tingkatkan Kepercayaan dengan <span>Sertifikasi Halal & ISO</span></h1>
            
            <p class="hero-description">
                Prima Halal Cendekia mendampingi perusahaan Anda meraih sertifikasi secara efisien, mulai dari penyusunan dokumen, perbaikan SOP, hingga sertifikat terbit.
            </p>

            <ul class="hero-benefits">
                <li>Bebas repot, kami siapkan dokumen & SOP</li>
                <li>Pendampingan komprehensif hingga lulus audit</li>
                <li>Keamanan data perusahaan terjamin 100%</li>
            </ul>

            <div class="mt-8">
                <a href="{{ config('company.whatsapp_url') }}" class="button button-primary button-large" target="_blank" rel="noopener noreferrer">
                    Konsultasi Gratis Sekarang
                </a>
            </div>
        </div>

        <figure class="hero-media" data-reveal data-reveal-direction="left">
            <!-- Fetch priority high and eager loading for LCP optimization -->
            <x-public.image 
                path="images/hero/hero-phc.jpg" 
                alt="Tim Prima Halal Cendekia melakukan pendampingan sertifikasi"
                width="800"
                height="600"
                fetchpriority="high"
                loading="eager"
            />
        </figure>
    </div>
</section>
