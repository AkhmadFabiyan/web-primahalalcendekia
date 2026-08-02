@extends('layouts.public')

@section('title', 'Solusi Tuntas Sertifikasi Halal & ISO | ' . config('company.name'))
@section('description', 'Pendampingan terintegrasi untuk sertifikasi Halal, ISO 22000, ISO 45001, dan HACCP. Kami bantu siapkan dokumen, SOP, hingga sertifikat terbit.')
@section('canonical', route('public.home'))

@push('head')
<link rel="preload" as="image" href="{{ asset('images/hero/hero-phc.jpg') }}" fetchpriority="high">
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Apakah dokumen dan SOP harus siap sebelum proses dimulai?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Tidak perlu. Salah satu nilai tambah kami adalah membantu Anda menyiapkan, menyusun, dan memperbaiki dokumen serta Standar Operasional Prosedur (SOP) sesuai persyaratan yang ditetapkan oleh lembaga sertifikasi."
      }
    },
    {
      "@type": "Question",
      "name": "Berapa lama estimasi proses sertifikasi hingga terbit?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Estimasi waktu bervariasi bergantung pada skala bisnis, kelengkapan dokumen awal, dan antrean di badan sertifikasi (seperti BPJPH untuk Halal). Namun, dengan pendampingan PHC, proses ini kami pastikan berjalan seefisien dan secepat mungkin karena minim revisi."
      }
    },
    {
      "@type": "Question",
      "name": "Apakah PHC mendampingi saat proses audit berlangsung?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Tentu saja. Kami tidak akan meninggalkan Anda saat audit. Konsultan kami akan melakukan pra-audit (simulasi) terlebih dahulu dan siap mendampingi tim Anda saat auditor eksternal melakukan pemeriksaan di lapangan."
      }
    },
    {
      "@type": "Question",
      "name": "Bagaimana dengan kerahasiaan data perusahaan (resep, bahan, dll)?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Kerahasiaan data klien adalah prioritas tertinggi kami. Sebelum proses pendampingan dimulai, kami selalu menandatangani Perjanjian Kerahasiaan (Non-Disclosure Agreement / NDA) yang mengikat secara hukum demi melindungi aset intelektual Anda."
      }
    }
  ]
}
</script>
@endpush

@section('content')
    @include('public.sections.hero')
    @include('public.sections.trust-marquee')
    @include('public.sections.problems')
    @include('public.sections.services')
    @include('public.sections.benefits')
    @include('public.sections.process')
    @include('public.sections.gallery')
    @include('public.sections.faq')
    @include('public.sections.call-to-action')
@endsection
