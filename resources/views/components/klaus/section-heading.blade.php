@props([
    'name' => 'clipboard',
    'title',
    'subtitle' => null,
])

<div class="flex items-start gap-4">
    <span class="klaus-icon-badge shrink-0">
        <x-klaus.icon :name="$name" class="h-5 w-5" />
    </span>
    <div>
        <h2 class="text-2xl font-black uppercase tracking-tight md:text-3xl">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-2 max-w-2xl text-klaus-black/60">{{ $subtitle }}</p>
        @endif
    </div>
</div>
