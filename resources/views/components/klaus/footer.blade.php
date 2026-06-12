<footer class="border-t-3 border-klaus-black bg-klaus-black text-klaus-cream">
    <div class="mx-auto max-w-6xl px-4 py-12 md:px-6">
        <div>
            <p class="text-2xl font-black uppercase tracking-tight">Klaus vom Amt</p>
            <p class="mt-2 text-klaus-pink">Explaining Germany.</p>
            <x-klaus.social-links variant="dark" class="mt-6" />
        </div>
        <div class="mt-8 space-y-2 border-t border-klaus-cream/20 pt-8 text-sm text-klaus-cream/70">
            <p>This website does not provide legal advice. It barely provides happiness.</p>
            <p>Powered by coffee and disappointment.</p>
            <nav class="flex flex-wrap gap-x-4 gap-y-1 pt-2" aria-label="Legal">
                <a href="{{ config('klaus.legal.terms_url') ?: route('legal.terms') }}" class="underline hover:text-klaus-cream">
                    Terms of Service
                </a>
                <a href="{{ config('klaus.legal.privacy_url') ?: route('legal.privacy') }}" class="underline hover:text-klaus-cream">
                    Privacy Policy
                </a>
            </nav>
        </div>
    </div>
</footer>
