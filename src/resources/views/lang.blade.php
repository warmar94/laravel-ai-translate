@php
    $allowedLangs = array_keys(config('translation.languages'));
    $currentPath = request()->getPathInfo();

    // Remove language prefix (/en/, /de/, etc)
    $cleanPath = preg_replace('/^\/[a-z]{2}(?=\/|$)/', '', $currentPath);
    $cleanPath = '/' . ltrim($cleanPath ?: '/', '/');

    $canonical = url($cleanPath === '/' ? '' : $cleanPath);
@endphp

<link rel="canonical" href="{{ $canonical }}">

<link rel="alternate" hreflang="x-default" href="{{ $canonical }}">

@foreach ($allowedLangs as $lang)
    @if($lang === 'en')
        <link rel="alternate" hreflang="en" href="{{ $canonical }}">
    @else
        <link rel="alternate" hreflang="{{ $lang }}" href="{{ url('/'.$lang.($cleanPath === '/' ? '' : $cleanPath)) }}">
    @endif
@endforeach