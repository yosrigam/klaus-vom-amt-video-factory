@props([
    'title',
    'updated' => 'June 12, 2026',
])

<article class="klaus-section">
    <div class="mx-auto max-w-3xl px-4 md:px-6">
        <a href="{{ url('/') }}" class="text-sm font-semibold text-klaus-black/60 hover:text-klaus-black">
            &larr; Back to Klaus vom Amt
        </a>

        <header class="mt-6 border-b-3 border-klaus-black pb-6">
            <h1 class="text-3xl font-black uppercase tracking-tight md:text-4xl">{{ $title }}</h1>
            <p class="mt-2 text-sm text-klaus-black/60">Last updated: {{ $updated }}</p>
        </header>

        <div class="prose-klaus mt-8 space-y-6 text-klaus-black/90">
            {{ $slot }}
        </div>
    </div>
</article>
