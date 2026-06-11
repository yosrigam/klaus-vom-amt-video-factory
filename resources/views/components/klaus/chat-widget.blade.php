<div class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-3">
    {{-- Chat panel --}}
    <div
        x-show="chatOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-4 opacity-0 scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="translate-y-4 opacity-0 scale-95"
        class="flex w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden rounded-2xl border-3 border-klaus-black bg-white shadow-[6px_6px_0_0_#1a1a1a]"
        role="dialog"
        aria-label="Ask Klaus chat"
    >
        <div class="flex items-center justify-between gap-3 border-b-3 border-klaus-black bg-klaus-pink/40 px-4 py-3">
            <div class="flex items-center gap-3">
                <x-klaus.profile-photo size="sm" variant="avatar" />
                <div>
                    <p class="text-sm font-black uppercase leading-tight">Ask Klaus</p>
                    <p class="text-xs font-semibold text-klaus-black/60">Average wait: forever</p>
                </div>
            </div>
            <button type="button" @click="closeChat()" class="klaus-icon-badge shrink-0" aria-label="Close chat">
                <x-klaus.icon name="close" class="h-4 w-4" />
            </button>
        </div>

        <div x-ref="chatLog" class="flex max-h-72 flex-col gap-3 overflow-y-auto bg-white p-4">
            <template x-for="(msg, i) in chatMessages" :key="i">
                <div class="flex" :class="msg.from === 'user' ? 'justify-end' : 'justify-start'">
                    <div
                        class="max-w-[85%] rounded-2xl border-3 border-klaus-black px-3 py-2 text-sm font-medium"
                        :class="msg.from === 'user' ? 'bg-klaus-pink' : 'bg-klaus-cream-dark'"
                        x-text="msg.text"
                    ></div>
                </div>
            </template>
        </div>

        <form @submit.prevent="askKlaus()" class="flex gap-2 border-t-3 border-klaus-black bg-klaus-cream-dark p-3">
            <input
                x-model="chatInput"
                x-ref="chatInput"
                type="text"
                placeholder="Ask Klaus anything…"
                class="klaus-input flex-1 py-2 text-sm"
            >
            <button type="submit" class="klaus-btn shrink-0 px-3 py-2 text-xs">Send</button>
        </form>
    </div>

    {{-- Toggle button --}}
    <button
        type="button"
        @click="toggleChat()"
        class="flex cursor-pointer items-center gap-2 rounded-full border-3 border-klaus-black bg-klaus-pink px-3 py-2 font-bold uppercase tracking-wide shadow-[4px_4px_0_0_#1a1a1a] transition hover:-translate-y-0.5 hover:shadow-[6px_6px_0_0_#1a1a1a] active:translate-y-0.5 active:shadow-[2px_2px_0_0_#1a1a1a] sm:px-4 sm:py-3"
        :aria-expanded="chatOpen"
    >
        <span x-show="chatOpen" x-cloak class="flex">
            <x-klaus.icon name="close" class="h-5 w-5" />
        </span>
        <span x-show="!chatOpen" class="flex">
            <x-klaus.profile-photo size="chat" variant="avatar" />
        </span>
        <span class="text-sm" x-text="chatOpen ? 'Close' : 'Ask Klaus'"></span>
    </button>
</div>
