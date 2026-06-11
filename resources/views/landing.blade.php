<x-layouts.klaus>
    <x-klaus.nav />

    {{-- Hero --}}
    <section
        id="home"
        class="relative scroll-mt-24 border-b-3 border-klaus-black bg-cover bg-top bg-no-repeat"
        style="background-image: url('{{ asset('images/klaus-banner.png') }}')"
    >
        <div class="mx-auto max-w-6xl px-4 pb-12 md:px-6 md:pb-16">
            <h1 class="sr-only">Klaus vom Amt — Your happiness is currently under administrative review.</h1>

            {{-- Spacer so the hero artwork stays visible in the background --}}
            <div class="aspect-[1024/425] w-full" aria-hidden="true"></div>

            <div class="-mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center md:-mt-10">
                <button type="button" @click="submitEvaluation()" class="klaus-btn">
                    Submit Yourself For Evaluation
                </button>
                <button type="button" @click="appealDecision()" class="klaus-btn klaus-btn-secondary">
                    Appeal A Previous Decision
                </button>
            </div>

            <x-klaus.social-links class="mt-6 justify-center" />

            {{-- Stats --}}
            <div class="mt-8 grid gap-4 md:grid-cols-3">
            <div class="klaus-stat">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border-3 border-klaus-black bg-klaus-pink/40">
                    <x-klaus.icon name="queue" class="h-5 w-5" />
                </div>
                <p class="text-xs font-bold uppercase tracking-wide text-klaus-black/50">Current queue position</p>
                <p class="mt-2 text-2xl font-black md:text-3xl">3,812,744</p>
                <p class="mt-1 text-sm text-klaus-black/60">people ahead of you</p>
            </div>
            <div class="klaus-stat">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border-3 border-klaus-black bg-klaus-pink/40">
                    <x-klaus.icon name="clock" class="h-5 w-5" />
                </div>
                <p class="text-xs font-bold uppercase tracking-wide text-klaus-black/50">Estimated processing time</p>
                <p class="mt-2 text-2xl font-black md:text-3xl">42y 7m 3w</p>
                <p class="mt-1 text-sm text-klaus-black/60">give or take a lifetime</p>
            </div>
            <div class="klaus-stat">
                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full border-3 border-klaus-black bg-klaus-pink/40">
                    <x-klaus.icon name="stamp" class="h-5 w-5 text-klaus-red" />
                </div>
                <p class="text-xs font-bold uppercase tracking-wide text-klaus-black/50">Office hours</p>
                <p class="mt-2 text-lg font-black md:text-xl">Monday–Neverday</p>
                <p class="mt-1 text-sm text-klaus-black/60">00:00–23:59. Closed Sundays. Emotionally unavailable Mondays.</p>
            </div>
            </div>
        </div>
    </section>

    {{-- Decisions --}}
    <section id="decisions" class="klaus-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-klaus.section-heading
                name="stamp"
                title="Recent Administrative Decisions"
                subtitle="All outcomes final. Especially the unfair ones."
            />

            <div class="mt-10 grid gap-4 md:grid-cols-3">
                @foreach ([
                    'Your recycling technique has been rejected.',
                    'Sunday enjoyment request denied.',
                    'Application to mow lawn peacefully denied.',
                ] as $decision)
                    <div class="klaus-card flex flex-col justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="klaus-icon-badge shrink-0">
                                <x-klaus.icon name="document" class="h-4 w-4" />
                            </span>
                            <p class="font-semibold leading-snug">{{ $decision }}</p>
                        </div>
                        <button type="button" @click="denyDecision()" class="klaus-btn klaus-btn-stamp w-full text-xs">
                            Denied
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Forms --}}
    <section id="forms" class="klaus-section bg-klaus-cream-dark/50">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-klaus.section-heading
                name="clipboard"
                title="Popular Fake Forms"
                subtitle="Download unavailable. Filing mandatory. Satisfaction optional."
            />

            <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    'Application for Weekend Enjoyment',
                    'Request to Feel Optimistic',
                    'Permission to Ignore Neighbor',
                    'Petition for Less Paperwork',
                    'Complaint About Complaint Procedure',
                ] as $form)
                    <button type="button" @click="downloadForm()" class="klaus-card text-left transition hover:-translate-y-1 hover:bg-klaus-pink/20">
                        <x-klaus.icon name="document" class="h-8 w-8" />
                        <p class="mt-3 font-bold">{{ $form }}</p>
                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-klaus-black/50">Form unavailable online</p>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Klaus Profile --}}
    <section id="profile" class="klaus-section bg-klaus-pink/20">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-klaus.section-heading
                name="profile"
                title="Klaus Profile"
                subtitle="Personal data processed with mild regret."
            />

            <div class="klaus-card mt-10 overflow-hidden p-0">
                <div class="grid lg:grid-cols-[minmax(0,240px)_1fr] lg:items-stretch">
                    <div class="flex flex-col border-b-3 border-klaus-black bg-gradient-to-b from-klaus-pink/60 to-klaus-pink/30 lg:border-b-0 lg:border-r-3">
                        <x-klaus.profile-photo
                            size="lg"
                            class="!block !h-auto !w-full !rounded-none !border-0"
                        />
                        <div class="flex flex-1 flex-col gap-3 p-4">
                            <dl class="space-y-2 text-[11px] leading-snug">
                                @foreach ([
                                    ['Name', 'Klaus-Dieter Schneider'],
                                    ['Dienststelle', 'Abt. für Antragsbearbeitung'],
                                    ['Position', 'Sachbearbeiter'],
                                    ['Mitarbeiter-Nr.', 'VMA-53-1987'],
                                    ['Eingestellt seit', '01.04.1994'],
                                ] as [$label, $value])
                                    <div>
                                        <dt class="font-black uppercase tracking-wide text-klaus-black/50">{{ $label }}</dt>
                                        <dd class="mt-0.5 font-bold text-klaus-black">{{ $value }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            <div class="mt-auto flex items-center gap-2 rounded-lg border-2 border-klaus-red/60 bg-klaus-cream/80 px-3 py-2">
                                <x-klaus.icon name="stamp" class="h-5 w-5 shrink-0 text-klaus-red" />
                                <p class="text-[10px] font-bold uppercase leading-tight text-klaus-red">In geprüfter Dienst</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 bg-klaus-cream-dark/40 p-5 md:grid-cols-2 md:p-6">
                        <dl class="space-y-4 text-sm">
                            @foreach ([
                                ['Full name', 'Klaus-Dieter Schneider'],
                                ['Public name', 'Klaus vom Amt'],
                                ['Age', '53'],
                                ['Occupation', 'Sachbearbeiter'],
                                ['Relationship status', 'Divorced since 2018'],
                                ['Children', 'Two'],
                                ['Custody', '50/50, managed through spreadsheets'],
                            ] as [$label, $value])
                                <div class="border-b border-klaus-black/10 pb-3">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-klaus-black/50">{{ $label }}</dt>
                                    <dd class="mt-1 font-semibold">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                        <dl class="space-y-4 text-sm">
                            @foreach ([
                                ['Car', 'Dark grey Skoda Octavia Combi, 11 years old, emotionally more stable than Klaus'],
                                ['Home', 'Reihenhaus with hedge, rain barrel, and correctly aligned bins'],
                                ['Favorite food', 'Bread, potato salad, and coffee'],
                                ['Greatest achievement', 'Renewed his parking permit on the first attempt'],
                                ['Biggest fear', 'Missing attachment'],
                                ['Secret', 'Klaus is actually nice. The system simply got to him first.'],
                            ] as [$label, $value])
                                <div class="border-b border-klaus-black/10 pb-3">
                                    <dt class="text-xs font-bold uppercase tracking-wide text-klaus-black/50">{{ $label }}</dt>
                                    <dd class="mt-1 font-semibold">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Running Gags --}}
            <div class="mt-16">
                <h3 class="text-2xl font-black uppercase tracking-tight md:text-3xl">
                    Klaus Private Life: Ongoing Administrative Damage
                </h3>
                <p class="mt-2 text-klaus-black/60">Recurring comedy categories. Emotionally tax-deductible.</p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['divorce', 'Divorce Bureaucracy', 'His divorce was peaceful, efficient, and only required 42 pages.'],
                        ['custody', '50/50 Custody Logistics', 'Klaus manages parenting through Google Calendar, spreadsheets, and emotional postponement.'],
                        ['car', 'The Car', 'His Skoda Octavia Combi has been declared dead three times. It continues out of spite.'],
                        ['children', 'The Children', 'Lukas says “Digga.” Emma understands bureaucracy better than Klaus.'],
                        ['reihenhaus', 'Reihenhaus Life', 'Hedge trimmed. Rain barrel full. Bins aligned. Feelings pending.'],
                        ['recycling', 'Recycling Obsession', 'Klaus has more recycling categories than emotions.'],
                        ['vacation', 'Vacation Routine', 'Same Baltic Sea trip every year. Klaus calls this adventure.'],
                        ['office', 'Office Trauma', 'Klaus is not powerful. He is middle management with a stamp.'],
                        ['food', 'Food Habits', 'Bread, potato salad, coffee, and quiet resignation.'],
                        ['technology', 'Technology Issues', 'Klaus trusts PDF. Everything else is suspicious.'],
                    ] as [$key, $title, $desc])
                        <div class="klaus-card flex flex-col gap-4">
                            <div>
                                <h4 class="font-black uppercase">{{ $title }}</h4>
                                <p class="mt-2 text-sm text-klaus-black/70">{{ $desc }}</p>
                            </div>
                            <button
                                type="button"
                                @click="revealFact('{{ $key }}')"
                                class="klaus-btn klaus-btn-secondary mt-auto w-full text-xs"
                            >
                                Reveal Klaus Fact
                            </button>
                        </div>
                    @endforeach
                </div>

                <div
                    x-show="revealedFact"
                    x-cloak
                    x-transition
                    class="klaus-card mt-6 border-klaus-pink-dark bg-klaus-pink/30"
                >
                    <p class="text-xs font-bold uppercase tracking-wide text-klaus-black/50">Classified Klaus Fact</p>
                    <p class="mt-2 text-lg font-semibold" x-text="revealedFact?.text"></p>
                </div>
            </div>
        </div>
    </section>

    {{-- Calculator --}}
    <section id="calculators" class="klaus-section">
        <div class="mx-auto max-w-6xl px-4 md:px-6">
            <x-klaus.section-heading
                name="calculator"
                title="Bureaucracy Calculator"
                subtitle="Quantify your unauthorized joy. For internal use only."
            />

            <div class="klaus-card mx-auto mt-10 max-w-xl">
                <div class="space-y-5">
                    <div>
                        <label class="klaus-label">Are you happy?</label>
                        <select x-model="calc.happy" class="klaus-input">
                            <option value="">Select compliance level…</option>
                            <option value="yes">Yes (suspicious)</option>
                            <option value="no">No (approved)</option>
                            <option value="maybe">Maybe (requires review)</option>
                        </select>
                    </div>
                    <div>
                        <label class="klaus-label">Have you completed Form B-17?</label>
                        <select x-model="calc.form" class="klaus-input">
                            <option value="">Select status…</option>
                            <option value="yes">Yes, in triplicate</option>
                            <option value="no">No</option>
                            <option value="what">What is Form B-17?</option>
                        </select>
                    </div>
                    <div>
                        <label class="klaus-label">Have you attached your soul?</label>
                        <select x-model="calc.soul" class="klaus-input">
                            <option value="">Select attachment…</option>
                            <option value="yes">Yes, notarized</option>
                            <option value="no">No</option>
                            <option value="partial">Partially (emotional PDF)</option>
                        </select>
                    </div>
                    <button type="button" @click="calculateRisk()" class="klaus-btn w-full">
                        Calculate Administrative Risk
                    </button>
                    <div
                        x-show="calc.result"
                        x-cloak
                        x-transition
                        class="rounded-xl border-3 border-klaus-red bg-klaus-red/10 px-4 py-4 text-center"
                    >
                        <p class="text-xs font-bold uppercase tracking-wide text-klaus-red">Official Result</p>
                        <p class="mt-2 text-xl font-black uppercase" x-text="calc.result"></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <x-klaus.footer />
</x-layouts.klaus>
