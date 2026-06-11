@props([
    'size' => 'md',
    'variant' => 'id-card',
])

@php
    $sizes = [
        'sm' => 'h-10 w-10',
        'md' => 'h-16 w-16',
        'lg' => 'w-full',
        'chat' => 'h-8 w-8',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $isAvatar = $variant === 'avatar';
    $src = $isAvatar
        ? asset('images/klaus-avatar.png')
        : asset('images/klaus-profile.png');
@endphp

@if ($isAvatar)
    <span
        {{ $attributes->merge(['class' => trim("inline-flex shrink-0 overflow-hidden rounded-full border-3 border-klaus-black {$sizeClass}")]) }}
    >
        <img
            src="{{ $src }}"
            alt="Klaus vom Amt"
            class="h-full w-full object-cover"
        >
    </span>
@else
    <img
        {{ $attributes->merge(['class' => trim("block h-auto w-full rounded-lg border-3 border-klaus-black {$sizeClass}")]) }}
        src="{{ $src }}"
        alt="Klaus vom Amt"
    >
@endif
