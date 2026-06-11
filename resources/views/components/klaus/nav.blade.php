<header class="sticky top-0 z-40 border-b-3 border-klaus-black bg-klaus-cream/95 backdrop-blur-sm">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
        <a href="#home" class="flex items-center gap-3">
            <span class="klaus-icon-badge shrink-0">
                <x-klaus.icon name="clipboard" class="h-5 w-5" />
            </span>
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-klaus-black/60">Official-ish Portal</p>
                <p class="text-sm font-black uppercase md:text-base">Klaus vom Amt</p>
            </div>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="Main navigation">
            @foreach ([
                ['home', 'Home'],
                ['decisions', 'Decisions'],
                ['forms', 'Forms'],
                ['profile', 'Klaus Profile'],
                ['calculators', 'Calculators'],
            ] as [$id, $label])
                <a href="#{{ $id }}" class="klaus-nav-link">{{ $label }}</a>
            @endforeach
            <button type="button" @click="openChat()" class="klaus-nav-link">Ask Klaus</button>
        </nav>

        <button
            type="button"
            class="klaus-icon-badge lg:hidden"
            @click="mobileNavOpen = !mobileNavOpen"
            aria-label="Toggle menu"
        >
            <x-klaus.icon name="menu" class="h-5 w-5" />
        </button>
    </div>

    <nav
        x-show="mobileNavOpen"
        x-cloak
        x-transition
        class="border-t-3 border-klaus-black bg-klaus-pink/20 lg:hidden"
    >
        <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-3">
            @foreach ([
                ['home', 'Home'],
                ['decisions', 'Decisions'],
                ['forms', 'Forms'],
                ['profile', 'Klaus Profile'],
                ['calculators', 'Calculators'],
            ] as [$id, $label])
                <a href="#{{ $id }}" @click="mobileNavOpen = false" class="klaus-nav-link">{{ $label }}</a>
            @endforeach
            <button type="button" @click="mobileNavOpen = false; openChat()" class="klaus-nav-link text-left">Ask Klaus</button>
        </div>
    </nav>
</header>
