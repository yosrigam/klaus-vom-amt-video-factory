@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'url' => null,
    'type' => 'website',
])

@php
    $seo = config('klaus.seo');
    $title = $title ?? $seo['title'];
    $description = $description ?? $seo['description'];
    $image = $image ?? $seo['image'];
    $url = $url ?? url('/');
    $imageUrl = str_starts_with($image, 'http') ? $image : asset($image);
@endphp

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@if ($seo['keywords'] ?? null)
    <meta name="keywords" content="{{ $seo['keywords'] }}">
@endif
<meta name="robots" content="index, follow">
<link rel="canonical" href="{{ $url }}">

<meta property="og:type" content="{{ $type }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $imageUrl }}">
@if ($seo['twitter_site'] ?? null)
    <meta name="twitter:site" content="{{ $seo['twitter_site'] }}">
@endif

<meta name="theme-color" content="#F5E6D3">
<link rel="icon" type="image/png" href="{{ asset('images/klaus-avatar.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/klaus-avatar.png') }}">

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebSite',
            '@id' => $url.'#website',
            'url' => $url,
            'name' => $seo['site_name'],
            'description' => $description,
            'inLanguage' => 'de-DE',
        ],
        [
            '@type' => 'WebPage',
            '@id' => $url.'#webpage',
            'url' => $url,
            'name' => $title,
            'description' => $description,
            'isPartOf' => ['@id' => $url.'#website'],
            'inLanguage' => 'de-DE',
        ],
        [
            '@type' => 'Person',
            '@id' => $url.'#klaus',
            'name' => 'Klaus vom Amt',
            'alternateName' => 'Klaus-Dieter Schneider',
            'description' => 'Satirical German office clerk. Sachbearbeiter at the Department of Application Processing.',
            'image' => asset('images/klaus-profile.png'),
            'jobTitle' => 'Sachbearbeiter',
            'worksFor' => [
                '@type' => 'Organization',
                'name' => 'Abt. für Antragsbearbeitung',
            ],
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
