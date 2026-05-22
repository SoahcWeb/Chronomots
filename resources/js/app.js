
import Alpine from 'alpinejs';
import { initAudioService } from './audio';

window.Alpine = Alpine;

const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
const prefersReducedMotion = () => motionQuery.matches;

const playSound = (sound) => {
    document.dispatchEvent(new CustomEvent('chronomots:play-sound', {
        detail: { sound },
    }));
};

const restartAnimation = (node, className, duration = 600) => {
    node.classList.remove(className);
    void node.offsetWidth;
    node.classList.add(className);
    window.setTimeout(() => node.classList.remove(className), duration);
};

Alpine.data('chronomotsTimer', (config = 0) => ({
    initialSeconds: typeof config === 'number'
        ? Number(config) || 0
        : Number(config?.initialSeconds) || 0,
    expiresAt: typeof config === 'object' ? config?.expiresAt ?? null : null,
    remaining: 0,
    expired: false,
    intervalId: null,
    lowTimeTriggered: false,

    init() {
        this.syncRemaining();
        this.applyVisualState();

        if (this.remaining <= 0) {
            this.expire();
            return;
        }

        this.intervalId = window.setInterval(() => {
            if (this.expiresAt) {
                this.syncRemaining();
            } else {
                this.remaining = Math.max(0, this.remaining - 1);
            }

            if (this.remaining <= 0) {
                this.expire();
                return;
            }

            if (this.remaining <= 10 && !this.lowTimeTriggered) {
                this.lowTimeTriggered = true;
                playSound('low-time');
                this.pulseRoot('chronomots-timer--alert', 650);
            }

            this.applyVisualState();
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

    syncRemaining() {
        if (this.expiresAt) {
            const expiresAtTimestamp = new Date(this.expiresAt).getTime();

            if (!Number.isNaN(expiresAtTimestamp)) {
                this.remaining = Math.max(0, Math.ceil((expiresAtTimestamp - Date.now()) / 1000));
                return;
            }
        }

        this.remaining = Math.max(0, this.remaining || this.initialSeconds);
    },

    expire() {
        this.remaining = 0;
        this.expired = true;
        this.applyVisualState();
        this.pulseRoot('chronomots-timer--expired-burst', 650);

        if (this.intervalId) {
            window.clearInterval(this.intervalId);
            this.intervalId = null;
        }
    },

    applyVisualState() {
        if (!this.$root) {
            return;
        }

        this.$root.toggleAttribute('data-timer-urgent', this.isUrgent);
        this.$root.toggleAttribute('data-timer-expired', this.expired);
    },

    pulseRoot(className, duration = 600) {
        if (prefersReducedMotion() || !this.$root) {
            return;
        }

        restartAnimation(this.$root, className, duration);
    },
}));

const revealNodes = (nodes, visibleClass, baseDelay = 50, stepDelay = 70) => {
    nodes.forEach((node, index) => {
        const explicitDelay = Number(node.dataset.feedbackDelay ?? Number.NaN);
        const delay = Number.isNaN(explicitDelay) ? baseDelay + (index * stepDelay) : explicitDelay;

        window.setTimeout(() => {
            node.classList.add(visibleClass);
        }, prefersReducedMotion() ? 0 : delay);
    });
};

const initFeedbackReveal = () => {
    document.body.classList.add('chronomots-js');

    revealNodes(
        [...document.querySelectorAll('[data-feedback-reveal]')],
        'chronomots-feedback-reveal--visible',
    );

    revealNodes(
        [...document.querySelectorAll('[data-feedback-token]')],
        'chronomots-feedback-token--visible',
        40,
        55,
    );

    document.querySelectorAll('[data-feedback-token="fresh"]').forEach((node) => {
        window.setTimeout(() => {
            node.classList.add('chronomots-feedback-token--fresh');
        }, prefersReducedMotion() ? 0 : 110);
    });

    revealNodes(
        [...document.querySelectorAll('[data-feedback-score]')],
        'chronomots-feedback-score--visible',
        120,
        90,
    );

    document.querySelectorAll('[data-feedback-error]').forEach((node) => {
        node.classList.add('chronomots-inline-feedback--active');
        node.closest('.chronomots-form-shell')?.classList.add('chronomots-feedback-error-shell');
    });

    document.querySelectorAll('[data-feedback-outcome]').forEach((node) => {
        window.setTimeout(() => {
            node.classList.add('chronomots-feedback-outcome--visible');
        }, prefersReducedMotion() ? 0 : 80);
    });
};

const initFeedbackSubmit = () => {
    document.querySelectorAll('form[data-feedback-submit]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.reportValidity()) {
                return;
            }

            form.classList.add('chronomots-form-shell--submitting');

            if (event.submitter instanceof HTMLElement) {
                event.submitter.classList.add('chronomots-button--busy');
            }

            const sound = form.dataset.feedbackSubmitSound;

            if (sound && sound !== 'none') {
                playSound(sound);
            }
        });
    });
};

const initPressableFeedback = () => {
    const pressables = document.querySelectorAll([
        'a.chronomots-button-primary',
        'a.chronomots-button-secondary',
        'button.chronomots-button-primary',
        'button.chronomots-button-secondary',
        '[data-feedback-press]',
    ].join(','));

    pressables.forEach((node) => {
        node.classList.add('chronomots-pressable');
        node.addEventListener('click', () => {
            if (prefersReducedMotion()) {
                return;
            }

            restartAnimation(node, 'chronomots-pressable--pulse', 320);
        });
    });
};

const initTimerFeedback = () => {
    document.querySelectorAll('[data-feedback-timer]').forEach((timer) => {
        let wasUrgent = timer.hasAttribute('data-timer-urgent');
        let wasExpired = timer.hasAttribute('data-timer-expired');

        const observer = new MutationObserver(() => {
            const isUrgent = timer.hasAttribute('data-timer-urgent');
            const isExpired = timer.hasAttribute('data-timer-expired');

            if (isUrgent && !wasUrgent && !prefersReducedMotion()) {
                restartAnimation(timer, 'chronomots-timer--alert', 650);
            }

            if (isExpired && !wasExpired && !prefersReducedMotion()) {
                restartAnimation(timer, 'chronomots-timer--expired-burst', 650);
            }

            wasUrgent = isUrgent;
            wasExpired = isExpired;
        });

        observer.observe(timer, {
            attributes: true,
            attributeFilter: ['data-timer-urgent', 'data-timer-expired'],
        });
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
initFeedbackSubmit();
initPressableFeedback();
initTimerFeedback();
registerServiceWorker();
Alpine.start();
