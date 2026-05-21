
import Alpine from 'alpinejs';
import { initAudioService } from './audio';

window.Alpine = Alpine;

const playSound = (sound) => {
    document.dispatchEvent(new CustomEvent('chronomots:play-sound', {
        detail: { sound },
    }));
};

Alpine.data('chronomotsTimer', (initialSeconds = 0) => ({
    remaining: Number(initialSeconds) || 0,
    expired: false,
    intervalId: null,
    lowTimeTriggered: false,

    init() {
        if (this.remaining <= 0) {
            this.expire();
            return;
        }

        this.intervalId = window.setInterval(() => {
            if (this.remaining <= 1) {
                this.expire();
                return;
            }

            this.remaining -= 1;

            if (this.remaining <= 10 && !this.lowTimeTriggered) {
                this.lowTimeTriggered = true;
                playSound('low-time');
            }
        }, 1000);
    },

    get minutes() {
        return String(Math.floor(this.remaining / 60)).padStart(2, '0');
    },

    get seconds() {
        return String(this.remaining % 60).padStart(2, '0');
    },

    get isUrgent() {
        return !this.expired && this.remaining > 0 && this.remaining < 10;
    },

    expire() {
        this.remaining = 0;
        this.expired = true;

        if (this.intervalId) {
            window.clearInterval(this.intervalId);
            this.intervalId = null;
        }
    },
}));

const initFeedbackReveal = () => {
    document.body.classList.add('chronomots-js');

    const revealNodes = document.querySelectorAll('[data-feedback-reveal]');

    revealNodes.forEach((node, index) => {
        window.setTimeout(() => {
            node.classList.add('chronomots-feedback-reveal--visible');
        }, 50 + (index * 70));
    });

    document.querySelectorAll('[data-feedback-error]').forEach((node) => {
        node.classList.add('chronomots-inline-feedback--active');
    });
};

const registerServiceWorker = () => {
    if (! ('serviceWorker' in navigator)) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch(() => {
            // Fallback silencieux: le jeu doit continuer normalement même sans service worker.
        });
    });
};

initAudioService();
initFeedbackReveal();
registerServiceWorker();
Alpine.start();
