<section class="py-16 bg-phc-primary-dark text-white text-center relative overflow-hidden" aria-labelledby="cta-heading">
    <!-- Decorative background elements -->
    <div class="absolute inset-0 z-0 opacity-20 pointer-events-none" aria-hidden="true">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-phc-gold rounded-full mix-blend-multiply filter blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-phc-primary-light rounded-full mix-blend-multiply filter blur-3xl"></div>
    </div>

    <div class="public-container narrow relative z-10" data-reveal>
        <h2 id="cta-heading" class="text-4xl md:text-5xl font-bold mb-6 tracking-tight">Siap Untuk Memulai?</h2>
        <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
            Jangan tunda lagi sertifikasi perusahaan Anda. Hubungi kami sekarang dan konsultasikan kebutuhan Anda secara gratis bersama tim ahli kami.
        </p>
        
        <a href="{{ config('company.whatsapp_url') }}?text=Halo%20Prima%20Halal%20Cendekia,%20saya%20ingin%20berkonsultasi%20mengenai%20layanan%20sertifikasi." 
           class="button button-gold button-large shadow-xl hover:shadow-phc-gold/20" 
           target="_blank" 
           rel="noopener noreferrer"
        >
            <x-icon.whatsapp class="w-6 h-6 mr-1" />
            Konsultasi Sekarang
        </a>
    </div>
</section>
