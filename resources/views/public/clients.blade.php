@extends('layouts.public')

@section('title', 'Portofolio & Klien Kami | ' . config('company.name'))
@section('description', 'Daftar klien yang telah mempercayakan pendampingan sertifikasi Halal dan ISO kepada Prima Halal Cendekia.')
@section('canonical', route('public.clients'))

@section('content')
    <!-- Hero Section -->
    <section class="py-24 bg-phc-surface border-b border-phc-border text-center" aria-labelledby="clients-hero">
        <div class="public-container narrow" data-reveal>
            <h1 id="clients-hero" class="text-4xl md:text-5xl font-bold mb-6">Portofolio & Klien Kami</h1>
            <p class="text-lg text-phc-muted mb-8 max-w-2xl mx-auto">
                Kepercayaan dari berbagai sektor industri menjadi bukti komitmen kami dalam memberikan layanan sertifikasi terbaik.
            </p>
        </div>
    </section>

    <!-- Trust Marquee Reused -->
    @include('public.sections.trust-marquee')

    <!-- Client Directory Section -->
    <livewire:public.client-directory />

    <!-- Call to Action -->
    @include('public.sections.call-to-action')
@endsection
