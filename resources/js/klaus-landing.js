import Alpine from 'alpinejs';
import {
    calculatorResults,
    deniedToasts,
    klausReplies,
    runningGagFacts,
} from './klaus/content';

const pick = (items) => items[Math.floor(Math.random() * items.length)];

Alpine.data('klausSite', () => ({
    modal: { open: false, title: '', body: '' },
    toasts: [],
    chatMessages: [
        {
            from: 'klaus',
            text: 'Klaus vom Amt here. State your request in triplicate. Emotionally.',
        },
    ],
    chatInput: '',
    chatOpen: false,
    calc: { happy: '', form: '', soul: '', result: null },
    revealedFact: null,
    mobileNavOpen: false,

    openModal(title, body) {
        this.modal = { open: true, title, body };
        document.body.style.overflow = 'hidden';
    },

    closeModal() {
        this.modal.open = false;
        document.body.style.overflow = '';
    },

    submitEvaluation() {
        this.openModal(
            'Thank you!',
            'Your request has been received and will be ignored in the order it was received.',
        );
    },

    appealDecision() {
        this.openModal(
            'Appeal Denied',
            'Your appeal was rejected because optimism was detected.',
        );
    },

    downloadForm() {
        this.openModal(
            'Download Failed',
            'This form is only available in person, during office hours, in another building.',
        );
    },

    denyDecision() {
        this.pushToast(pick(deniedToasts));
    },

    pushToast(message) {
        const id = Date.now() + Math.random();
        this.toasts.push({ id, message });
        setTimeout(() => {
            this.toasts = this.toasts.filter((t) => t.id !== id);
        }, 4200);
    },

    toggleChat() {
        this.chatOpen = ! this.chatOpen;
        if (this.chatOpen) {
            this.$nextTick(() => {
                this.$refs.chatInput?.focus();
                this.$refs.chatLog?.scrollTo({ top: this.$refs.chatLog.scrollHeight });
            });
        }
    },

    openChat() {
        if (! this.chatOpen) {
            this.toggleChat();
        }
    },

    closeChat() {
        this.chatOpen = false;
    },

    askKlaus() {
        const question = this.chatInput.trim();
        if (! question) {
            return;
        }

        this.chatMessages.push({ from: 'user', text: question });
        this.chatInput = '';
        this.$nextTick(() => {
            this.$refs.chatLog?.scrollTo({ top: this.$refs.chatLog.scrollHeight, behavior: 'smooth' });
        });

        setTimeout(() => {
            this.chatMessages.push({ from: 'klaus', text: pick(klausReplies) });
            this.$nextTick(() => {
                this.$refs.chatLog?.scrollTo({ top: this.$refs.chatLog.scrollHeight, behavior: 'smooth' });
            });
        }, 600);
    },

    calculateRisk() {
        this.calc.result = pick(calculatorResults);
    },

    revealFact(category) {
        this.revealedFact = { category, text: pick(runningGagFacts[category]) };
    },
}));

window.Alpine = Alpine;
Alpine.start();
