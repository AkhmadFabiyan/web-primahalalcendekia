<!doctype html>
<html lang="id" class="scroll-smooth no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', '')">
    <meta name="robots" content="@yield('robots', 'index, follow')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ config('company.name') }}">
    <meta property="og:title" content="@yield('title', config('company.name'))">
    <meta property="og:description" content="@yield('description', '')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:image" content="{{ asset('images/og/primahalalcendekia.png') }}">

    <title>@yield('title', config('company.name'))</title>

    @vite(['resources/css/public-site.css', 'resources/js/public-site.js'])
    @stack('head')
    
    <!-- NO-JS Fallback handled in CSS -->
    <noscript>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </noscript>

    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@graph": [
        {
          "@type": "WebSite",
          "@id": "{{ url('/') }}#website",
          "url": "{{ url('/') }}",
          "name": "{{ config('company.name') }}",
          "description": "{{ config('company.description', 'Pendampingan terintegrasi untuk sertifikasi Halal, ISO 22000, ISO 45001, dan HACCP.') }}",
          "inLanguage": "id-ID"
        },
        {
          "@type": "Organization",
          "@id": "{{ url('/') }}#organization",
          "name": "{{ config('company.name') }}",
          "url": "{{ url('/') }}",
          "logo": {
            "@type": "ImageObject",
            "url": "{{ asset('images/logo.png') }}"
          },
          "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "{{ config('company.phone_e164') }}",
            "contactType": "customer service",
            "areaServed": "ID",
            "availableLanguage": "Indonesian"
          }
        }
      ]
    }
    </script>
    @livewireStyles
</head>
<body class="public-site">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <x-public.header />

    <main id="main-content">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <x-public.footer />
    <x-public.floating-whatsapp />

    @stack('scripts')
    
    <script>
        // Global handler for broken images (Skeleton loading fallback)
        document.addEventListener('error', function (event) {
            if (event.target.tagName && event.target.tagName.toLowerCase() === 'img') {
                if (!event.target.dataset.fallbackApplied) {
                    event.target.dataset.fallbackApplied = 'true';
                    // Tambahkan class skeleton (animate-pulse) dan background fallback yang netral
                    event.target.classList.add('animate-pulse', 'bg-gray-200');
                    // Ganti resource src dengan SVG transparan kosong untuk mencegah icon broken image browser
                    event.target.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCI+PC9zdmc+';
                }
            }
        }, true); // Use capture phase because error events don't bubble
    </script>
    @livewireScripts
</body>
</html>
