

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('chronomotsTimer', (initialSeconds = 0) => ({
    remaining: Number(initialSeconds) || 0,
    expired: false,
    intervalId: null,

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

Alpine.start();
