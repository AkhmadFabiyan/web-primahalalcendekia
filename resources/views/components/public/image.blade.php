@props([
    'path',
    'alt' => '',
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'fetchpriority' => 'auto',
    'class' => ''
])

@php
    // Hanya menebak versi webp/avif jika ekstensi standar gambar raster
    $isRaster = preg_match('/\.(jpg|jpeg|png)$/i', $path);
    
    $avifPath = $isRaster ? preg_replace('/\.(jpg|jpeg|png)$/i', '.avif', $path) : null;
    $webpPath = $isRaster ? preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $path) : null;
    
    $hasAvif = $avifPath && file_exists(public_path($avifPath));
    $hasWebp = $webpPath && file_exists(public_path($webpPath));
@endphp

<picture>
    @if($hasAvif)
        <source type="image/avif" srcset="{{ asset($avifPath) }}">
    @endif
    @if($hasWebp)
        <source type="image/webp" srcset="{{ asset($webpPath) }}">
    @endif
    <img 
        src="{{ asset($path) }}" 
        alt="{{ $alt }}"
        @if($width) width="{{ $width }}" @endif
        @if($height) height="{{ $height }}" @endif
        loading="{{ $loading }}"
        fetchpriority="{{ $fetchpriority }}"
        decoding="{{ $loading === 'eager' ? 'sync' : 'async' }}"
        class="{{ $class }}"
    >
</picture>
