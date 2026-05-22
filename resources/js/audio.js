class AudioService {
    constructor({
        soundEnabled = true,
        musicEnabled = false,
        volumeLevel = 70,
    } = {}) {
        this.soundEnabled = soundEnabled;
        this.musicEnabled = musicEnabled;
        this.volumeLevel = this.normalizeVolume(volumeLevel);
        this.audioContext = null;
        this.lastPlayedAt = new Map();
        this.resumeBound = false;
    }

    normalizeVolume(value) {
        const normalized = Number(value);

        if (Number.isNaN(normalized)) {
            return 0.7;
        }

        return Math.min(1, Math.max(0, normalized / 100));
    }

    updatePreferences({ soundEnabled, musicEnabled, volumeLevel } = {}) {
        if (typeof soundEnabled === 'boolean') {
            this.soundEnabled = soundEnabled;
        }

        if (typeof musicEnabled === 'boolean') {
            this.musicEnabled = musicEnabled;
        }

        if (volumeLevel !== undefined) {
            this.volumeLevel = this.normalizeVolume(volumeLevel);
        }
    }

    ensureContext() {
        if (! window.AudioContext && ! window.webkitAudioContext) {
            return null;
        }

        if (! this.audioContext) {
            const Context = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new Context();
        }

        if (! this.resumeBound) {
            this.resumeBound = true;

            ['pointerdown', 'touchstart', 'keydown'].forEach((eventName) => {
                window.addEventListener(eventName, () => this.resume(), { passive: true });
            });
        }

        return this.audioContext;
    }

    resume() {
        const context = this.ensureContext();

        if (context && context.state === 'suspended') {
            void context.resume();
        }
    }

    canPlay(name) {
        if (! this.soundEnabled) {
            return false;
        }

        const now = Date.now();
        const previousTime = this.lastPlayedAt.get(name) ?? 0;
        const cooldown = name === 'low-time' ? 8000 : 250;

        if ((now - previousTime) < cooldown) {
            return false;
        }

        this.lastPlayedAt.set(name, now);

        return true;
    }

    play(name) {
        if (! this.canPlay(name)) {
            return;
        }

        const context = this.ensureContext();

        if (! context) {
            return;
        }

        const startAt = context.currentTime + 0.02;

        switch (name) {
            case 'valid':
                this.playSequence([[660, 0.08, 'triangle'], [880, 0.12, 'sine']], startAt, 0.95);
                break;
            case 'error':
                this.playSequence([[270, 0.12, 'sawtooth'], [190, 0.16, 'triangle']], startAt, 0.7);
                break;
            case 'victory':
                this.playSequence([[520, 0.08, 'triangle'], [740, 0.1, 'triangle'], [988, 0.16, 'sine']], startAt, 1);
                break;
            case 'defeat':
                this.playSequence([[440, 0.12, 'triangle'], [330, 0.13, 'triangle'], [220, 0.18, 'sine']], startAt, 0.75);
                break;
            case 'low-time':
                this.playSequence([[988, 0.06, 'square'], [988, 0.05, 'square']], startAt, 0.4, 0.12);
                break;
            case 'achievement':
                this.playSequence([[784, 0.06, 'triangle'], [1046, 0.09, 'triangle'], [1318, 0.15, 'sine']], startAt, 1);
                break;
            case 'draw':
                this.playSequence([[620, 0.05, 'triangle'], [760, 0.07, 'sine']], startAt, 0.55, 0.04);
                break;
            default:
                break;
        }
    }

    playSequence(notes, startAt, intensity = 1, gap = 0.05) {
        const context = this.ensureContext();

        if (! context) {
            return;
        }

        let currentTime = startAt;

        notes.forEach(([frequency, duration, type]) => {
            this.playTone(frequency, currentTime, duration, type, intensity);
            currentTime += duration + gap;
        });
    }

    playTone(frequency, startAt, duration, type, intensity = 1) {
        const context = this.ensureContext();

        if (! context) {
            return;
        }

        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        const peakVolume = Math.max(0.01, this.volumeLevel * 0.12 * intensity);

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, startAt);

        gainNode.gain.setValueAtTime(0.0001, startAt);
        gainNode.gain.linearRampToValueAtTime(peakVolume, startAt + 0.02);
        gainNode.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

        oscillator.connect(gainNode);
        gainNode.connect(context.destination);

        oscillator.start(startAt);
        oscillator.stop(startAt + duration + 0.03);
    }
}

const boolFromDataset = (value, fallback) => {
    if (value === undefined) {
        return fallback;
    }

    return value === '1' || value === 'true';
};

const initAudioService = () => {
    const { dataset } = document.body;
    const audioService = new AudioService({
        soundEnabled: boolFromDataset(dataset.audioSoundEnabled, true),
        musicEnabled: boolFromDataset(dataset.audioMusicEnabled, false),
        volumeLevel: dataset.audioVolumeLevel ?? 70,
    });

    document.addEventListener('chronomots:play-sound', (event) => {
        const sound = event.detail?.sound;

        if (typeof sound === 'string') {
            audioService.play(sound);
        }
    });

    document.querySelectorAll('[data-audio-autoplay]').forEach((element, index) => {
        const sound = element.dataset.audioAutoplay;

        if (! sound) {
            return;
        }

        window.setTimeout(() => audioService.play(sound), 140 * (index + 1));
    });

    const preferencesForm = document.querySelector('[data-audio-preferences-form]');
    const volumeInput = preferencesForm?.querySelector('[data-audio-volume-input]');
    const volumeLabel = preferencesForm?.querySelector('[data-audio-volume-label]');

    if (preferencesForm instanceof HTMLFormElement) {
        const syncPreferences = () => {
            const soundEnabled = preferencesForm.querySelector('input[name="sound_enabled"]')?.checked ?? true;
            const musicEnabled = preferencesForm.querySelector('input[name="music_enabled"]')?.checked ?? false;
            const volumeLevel = volumeInput?.value ?? 70;

            audioService.updatePreferences({
                soundEnabled,
                musicEnabled,
                volumeLevel,
            });

            if (volumeLabel) {
                volumeLabel.textContent = `${volumeLevel}%`;
            }
        };

        syncPreferences();

        preferencesForm.addEventListener('change', (event) => {
            syncPreferences();

            if (event.target instanceof HTMLInputElement && event.target.type === 'checkbox') {
                audioService.play('valid');
            }
        });

        volumeInput?.addEventListener('input', () => {
            syncPreferences();
        });
    }

    return audioService;
};

export { initAudioService };
