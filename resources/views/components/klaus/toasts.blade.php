<div class="pointer-events-none fixed bottom-4 left-4 z-40 flex w-full max-w-sm flex-col gap-3 px-4 sm:px-0">
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-8 opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto klaus-card border-klaus-red bg-klaus-cream-dark py-3 text-sm font-semibold"
            x-text="toast.message"
        ></div>
    </template>
</div>
