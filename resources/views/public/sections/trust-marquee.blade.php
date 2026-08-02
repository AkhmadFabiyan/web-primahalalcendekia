<section class="trust-marquee" aria-labelledby="trust-heading">
    <div class="public-container">
        <h2 id="trust-heading" class="sr-only">Klien yang Mempercayai Kami</h2>
        <div class="marquee-wrapper">
            <p class="text-center text-sm font-semibold text-phc-muted mb-4 uppercase tracking-wider">
                Dipercaya oleh berbagai instansi dan perusahaan terkemuka
            </p>
            <div class="marquee-overflow">
                <div class="trust-track flex items-center gap-12 py-4">
                    <!-- Duplicate items to create infinite scroll effect (CSS animation defined in public-site.css) -->
                    @for($i = 0; $i < 2; $i++)
                        <!-- Placeholder logos -->
                        <div class="grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                            <img src="{{ asset('images/clients/placeholder-1.png') }}" alt="Klien 1" width="120" height="60" loading="lazy">
                        </div>
                        <div class="grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                            <img src="{{ asset('images/clients/placeholder-2.png') }}" alt="Klien 2" width="120" height="60" loading="lazy">
                        </div>
                        <div class="grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                            <img src="{{ asset('images/clients/placeholder-3.png') }}" alt="Klien 3" width="120" height="60" loading="lazy">
                        </div>
                        <div class="grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                            <img src="{{ asset('images/clients/placeholder-4.png') }}" alt="Klien 4" width="120" height="60" loading="lazy">
                        </div>
                        <div class="grayscale opacity-50 hover:grayscale-0 hover:opacity-100 transition-all duration-300">
                            <img src="{{ asset('images/clients/placeholder-5.png') }}" alt="Klien 5" width="120" height="60" loading="lazy">
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</section>
