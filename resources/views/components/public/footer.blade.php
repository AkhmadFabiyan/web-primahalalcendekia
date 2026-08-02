<footer class="site-footer bg-phc-primary pt-16">
    <div class="public-container grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_1.5fr] gap-12 lg:gap-8">
        <section class="footer-brand flex flex-col gap-6" aria-labelledby="footer-brand-title">
            <div class="brand brand-on-dark flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" width="48" height="48" loading="lazy" class="rounded-md bg-white/10">
                <h2 id="footer-brand-title" class="font-bold text-xl">{{ config('company.name') }}</h2>
            </div>
            <p class="text-white/70 text-sm leading-relaxed max-w-xs">Mitra pendampingan sertifikasi Halal, ISO, dan HACCP untuk berbagai bidang usaha.</p>
            
            <div class="mt-2">
                <h3 class="text-sm font-semibold mb-4 text-white">Media Sosial</h3>
                <div class="flex items-center gap-4">
                    @if(config('company.instagram_url'))
                    <a href="{{ config('company.instagram_url') }}" target="_blank" rel="noopener noreferrer" class="text-white/70 hover:text-white hover:scale-110 transition-all" aria-label="Instagram">
                        <x-lucide-instagram class="w-5 h-5" />
                    </a>
                    @endif
                    
                    @if(config('company.tiktok_url'))
                    <a href="{{ config('company.tiktok_url') }}" target="_blank" rel="noopener noreferrer" class="text-white/70 hover:text-white hover:scale-110 transition-all" aria-label="TikTok">
                        <x-icon.tiktok class="w-5 h-5" />
                    </a>
                    @endif
                </div>
            </div>
        </section>

        <nav aria-label="Layanan" class="flex flex-col gap-4">
            <h2 class="font-bold text-lg text-white">Layanan</h2>
            <div class="flex flex-col gap-3 text-sm">
                <a href="{{ route('public.home') }}#layanan" class="text-white/70 hover:text-white w-fit transition-colors">Sertifikasi Halal</a>
                <a href="{{ route('public.home') }}#layanan" class="text-white/70 hover:text-white w-fit transition-colors">ISO 22000</a>
                <a href="{{ route('public.home') }}#layanan" class="text-white/70 hover:text-white w-fit transition-colors">ISO 45001</a>
                <a href="{{ route('public.home') }}#layanan" class="text-white/70 hover:text-white w-fit transition-colors">HACCP</a>
            </div>
        </nav>

        <nav aria-label="Navigasi footer" class="flex flex-col gap-4">
            <h2 class="font-bold text-lg text-white">Navigasi</h2>
            <div class="flex flex-col gap-3 text-sm">
                <a href="{{ route('public.home') }}" class="text-white/70 hover:text-white w-fit transition-colors">Beranda</a>
                @if (Route::has('public.clients'))
                    <a href="{{ route('public.clients') }}" class="text-white/70 hover:text-white w-fit transition-colors">Klien</a>
                @endif
                <a href="{{ route('public.home') }}#faq" class="text-white/70 hover:text-white w-fit transition-colors">FAQ</a>
                <a href="{{ url('/login') }}" class="text-white/70 hover:text-white w-fit transition-colors">Masuk Sistem</a>
            </div>
        </nav>

        <section aria-labelledby="footer-contact-title" class="flex flex-col gap-4">
            <h2 id="footer-contact-title" class="font-bold text-lg text-white">Kontak Kami</h2>
            <address class="not-italic flex flex-col gap-4 text-sm text-white/70">
                <div class="flex items-start gap-3">
                    <x-lucide-map-pin class="w-5 h-5 flex-shrink-0 mt-0.5" />
                    <span class="leading-relaxed">{{ config('company.address') }}</span>
                </div>
                <div class="flex items-center gap-3">
                    <x-icon.whatsapp class="w-5 h-5 flex-shrink-0" />
                    <a href="{{ config('company.whatsapp_url') }}" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors m-0!">{{ config('company.phone_display') }}</a>
                </div>
                <div class="flex items-center gap-3">
                    <x-lucide-mail class="w-5 h-5 flex-shrink-0" />
                    <a href="mailto:{{ config('company.email') }}" class="hover:text-white transition-colors m-0!">{{ config('company.email') }}</a>
                </div>
            </address>
        </section>
    </div>

    <div class="public-container footer-bottom text-center">
        <p>&copy; {{ now()->year }} {{ config('company.name') }}. Hak cipta dilindungi.</p>
    </div>
</footer>
