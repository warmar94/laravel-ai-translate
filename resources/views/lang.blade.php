@php
    $allowedLangs = array_keys(config('translation.languages'));
    $currentPath = request()->getPathInfo();
    $cleanPath = preg_replace('/^\/(' . implode('|', $allowedLangs) . ')/', '', $currentPath) ?: '/';
    $cleanPath = '/' . ltrim($cleanPath, '/');
@endphp

{{-- Canonical should be English without prefix --}}
<link rel="canonical" href="{{ url($cleanPath === '/' ? '' : $cleanPath) }}" />

{{-- x-default should also be English without prefix --}}
<link rel="alternate" hreflang="x-default" href="{{ url($cleanPath === '/' ? '' : $cleanPath) }}" />

{{-- Language alternates --}}
@foreach ($allowedLangs as $lang)
    @if($lang === 'en')
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ url($cleanPath === '/' ? '' : $cleanPath) }}" />
    @else
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ url('/' . $lang . ($cleanPath === '/' ? '' : $cleanPath)) }}" />
    @endif
@endforeach

<link rel="shortcut icon" type="image/x-icon" href="https://licenseplate.ae/assets/images/logo/logo-dark.webp">