@props([
    'variant' => 'light',
])

@php
    $links = [
        ['url' => config('klaus.social.instagram'), 'icon' => 'instagram', 'label' => 'Instagram'],
        ['url' => config('klaus.social.youtube'), 'icon' => 'youtube', 'label' => 'YouTube'],
        ['url' => config('klaus.social.tiktok'), 'icon' => 'tiktok', 'label' => 'TikTok'],
    ];

    $linkClass = $variant === 'dark'
        ? 'klaus-social-link klaus-social-link-dark'
        : 'klaus-social-link';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }}>
    @foreach ($links as $link)
        <a
            href="{{ $link['url'] }}"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="{{ $link['label'] }}"
            class="{{ $linkClass }}"
        >
            <x-klaus.icon :name="$link['icon']" class="h-5 w-5" />
            <span class="text-sm font-bold">{{ $link['label'] }}</span>
        </a>
    @endforeach
</div>
