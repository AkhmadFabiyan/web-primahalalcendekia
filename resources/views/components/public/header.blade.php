<header class="site-header" data-site-header>
    <div class="public-container header-inner">
        <a href="{{ route('public.home') }}" class="brand" aria-label="{{ config('company.name') }} — Beranda">
            <img
                src="{{ asset('images/logo.png') }}"
                alt="Logo"
                class="w-8 h-8 md:w-10 md:h-10 rounded bg-gray-200"
            >
            <span>Prima Halal <strong>Cendekia</strong></span>
        </a>

        <nav class="desktop-navigation" aria-label="Navigasi utama">
            <a href="{{ route('public.home') }}#beranda">Beranda</a>
            <a href="{{ route('public.home') }}#masalah">Masalah</a>
            <a href="{{ route('public.home') }}#layanan">Layanan</a>
            <a href="{{ route('public.home') }}#proses">Proses</a>
            @if (Route::has('public.clients'))
                <a href="{{ route('public.clients') }}" @class(['is-active' => request()->routeIs('public.clients')])>Klien</a>
            @endif
            <a href="{{ route('public.home') }}#faq">FAQ</a>
        </nav>

        <div class="header-actions">
            @guest
                <a href="{{ url('/login') }}" class="button button-outline">Masuk Sistem</a>
            @else
                <a href="{{ url('/dashboard') }}" class="button button-outline">Dashboard</a>
            @endguest

            <a
                href="{{ config('company.whatsapp_url') }}"
                class="button button-gold desktop-consultation"
                target="_blank"
                rel="noopener noreferrer"
            >
                Konsultasi Gratis
            </a>

            <button
                type="button"
                class="mobile-menu-toggle"
                aria-label="Buka menu"
                aria-expanded="false"
                aria-controls="mobile-navigation"
                data-menu-toggle
            >
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </button>
        </div>
    </div>

    <div id="mobile-navigation" class="mobile-navigation" hidden data-mobile-menu>
        <nav aria-label="Navigasi mobile">
            <a href="{{ route('public.home') }}#beranda">Beranda</a>
            <a href="{{ route('public.home') }}#masalah">Masalah</a>
            <a href="{{ route('public.home') }}#layanan">Layanan</a>
            <a href="{{ route('public.home') }}#proses">Proses</a>
            @if (Route::has('public.clients'))
                <a href="{{ route('public.clients') }}">Klien</a>
            @endif
            <a href="{{ route('public.home') }}#faq">FAQ</a>
            @guest
                <a href="{{ url('/login') }}">Masuk Sistem</a>
            @else
                <a href="{{ url('/dashboard') }}">Dashboard</a>
            @endguest
        </nav>
    </div>
</header>
