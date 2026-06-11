<div
    x-show="modal.open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="closeModal()"
>
    <div class="absolute inset-0 cursor-pointer bg-klaus-black/60" @click="closeModal()"></div>

    <div
        x-show="modal.open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative z-10 w-full max-w-md klaus-card"
        role="dialog"
        aria-modal="true"
    >
        <div class="mb-4 flex items-start justify-between gap-4">
            <h3 class="text-xl font-black uppercase tracking-tight" x-text="modal.title"></h3>
            <button type="button" @click="closeModal()" class="klaus-icon-badge shrink-0" aria-label="Close">
                <x-klaus.icon name="close" class="h-4 w-4" />
            </button>
        </div>
        <p class="text-base leading-relaxed text-klaus-black/80" x-text="modal.body"></p>
        <button type="button" @click="closeModal()" class="klaus-btn mt-6 w-full">Acknowledged With Resignation</button>
    </div>
</div>
