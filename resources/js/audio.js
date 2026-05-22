const AUDIO_STORAGE_KEY = 'chronomots.audio.preferences.v1';

const SOUND_ALIASES = {
    draw: 'letter-reveal',
    valid: 'word-valid',
};

const SOUND_LIBRARY = {
    'letter-reveal': {
        cooldown: 90,
        intensity: 0.48,
        gap: 0.035,
        notes: [
            [620, 0.05, 'triangle'],
            [770, 0.055, 'sine'],
        ],
    },
    'word-valid': {
        cooldown: 240,
        intensity: 0.82,
        gap: 0.045,
        notes: [
            [660, 0.07, 'triangle'],
            [880, 0.11, 'sine'],
        ],
    },
    error: {
        cooldown: 300,
        intensity: 0.56,
        gap: 0.05,
        notes: [
            [290, 0.09, 'triangle'],
            [210, 0.11, 'sawtooth'],
        ],
    },
    victory: {
        cooldown: 1200,
        intensity: 0.95,
        gap: 0.05,
        notes: [
            [520, 0.08, 'triangle'],
            [784, 0.1, 'triangle'],
            [1046, 0.16, 'sine'],
        ],
    },
    defeat: {
        cooldown: 1200,
        intensity: 0.62,
        gap: 0.055,
        notes: [
            [430, 0.1, 'triangle'],
            [320, 0.12, 'triangle'],
            [230, 0.16, 'sine'],
        ],
    },
    'low-time': {
        cooldown: 8000,
        intensity: 0.34,
        gap: 0.11,
        notes: [
            [988, 0.055, 'square'],
            [988, 0.05, 'square'],
        ],
    },
    achievement: {
        cooldown: 1400,
        intensity: 0.9,
        gap: 0.045,
        notes: [
            [784, 0.055, 'triangle'],
            [1046, 0.08, 'triangle'],
            [1318, 0.14, 'sine'],
        ],
    },
};

const boolFromDataset = (value, fallback) => {
    if (value === undefined) {
        return fallback;
    }

    return value === '1' || value === 'true';
};

const safeStorage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Fallback silencieux: le jeu continue sans persistance locale.
        }
    },
};

const readStoredPreferences = () => {
    const raw = safeStorage.get(AUDIO_STORAGE_KEY);

    if (! raw) {
        return null;
    }

    try {
        const parsed = JSON.parse(raw);

        return typeof parsed === 'object' && parsed !== null ? parsed : null;
    } catch {
        return null;
    }
};

class AudioService {
    constructor({
        soundEnabled = true,
        musicEnabled = false,
        volumeLevel = 70,
        muted = false,
    } = {}) {
        this.soundEnabled = soundEnabled;
        this.musicEnabled = musicEnabled;
        this.muted = muted;
        this.volumeLevel = this.normalizeVolume(volumeLevel);
        this.audioContext = null;
        this.masterGain = null;
        this.compressor = null;
        this.lastPlayedAt = new Map();
        this.pendingQueue = [];
        this.resumeBound = false;
        this.hasUnlockedAudio = false;
        this.statusText = '';
    }

    normalizeVolume(value) {
        const normalized = Number(value);

        if (Number.isNaN(normalized)) {
            return 0.7;
        }

        return Math.min(1, Math.max(0, normalized / 100));
    }

    get volumePercent() {
        return Math.round(this.volumeLevel * 100);
    }

    get isAudioAllowed() {
        return this.soundEnabled && ! this.muted;
    }

    get isSupported() {
        return Boolean(window.AudioContext || window.webkitAudioContext);
    }

    normalizeSoundName(name) {
        return SOUND_ALIASES[name] ?? name;
    }

    serializePreferences() {
        return {
            soundEnabled: this.soundEnabled,
            musicEnabled: this.musicEnabled,
            muted: this.muted,
            volumeLevel: this.volumePercent,
        };
    }

    persistPreferences() {
        safeStorage.set(AUDIO_STORAGE_KEY, JSON.stringify(this.serializePreferences()));
    }

    updatePreferences({
        soundEnabled,
        musicEnabled,
        volumeLevel,
        muted,
        persist = true,
        announce = true,
    } = {}) {
        if (typeof soundEnabled === 'boolean') {
            this.soundEnabled = soundEnabled;
        }

        if (typeof musicEnabled === 'boolean') {
            this.musicEnabled = musicEnabled;
        }

        if (typeof muted === 'boolean') {
            this.muted = muted;
        }

        if (volumeLevel !== undefined) {
            this.volumeLevel = this.normalizeVolume(volumeLevel);
        }

        if (this.masterGain) {
            const context = this.ensureContext();

            if (context) {
                this.masterGain.gain.setTargetAtTime(
                    this.isAudioAllowed ? Math.max(0.0001, this.volumeLevel) : 0.0001,
                    context.currentTime,
                    0.02,
                );
            }
        }

        if (persist) {
            this.persistPreferences();
        }

        if (announce) {
            this.syncDomState();
        }
    }

    toggleMute() {
        this.updatePreferences({
            muted: ! this.muted,
        });

        if (! this.muted) {
            this.play('word-valid', {
                interactive: true,
                bypassCooldown: true,
            });
        }
    }

    ensureContext() {
        if (! this.isSupported) {
            return null;
        }

        if (! this.audioContext) {
            const Context = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new Context();
            this.setupGraph(this.audioContext);
        }

        if (! this.resumeBound) {
            this.resumeBound = true;

            ['pointerdown', 'touchstart', 'keydown'].forEach((eventName) => {
                window.addEventListener(eventName, () => {
                    this.resume();
                }, { passive: true });
            });
        }

        return this.audioContext;
    }

    setupGraph(context) {
        this.compressor = context.createDynamicsCompressor();
        this.compressor.threshold.value = -20;
        this.compressor.knee.value = 24;
        this.compressor.ratio.value = 3;
        this.compressor.attack.value = 0.003;
        this.compressor.release.value = 0.2;

        this.masterGain = context.createGain();
        this.masterGain.gain.value = this.isAudioAllowed ? Math.max(0.0001, this.volumeLevel) : 0.0001;

        this.compressor.connect(this.masterGain);
        this.masterGain.connect(context.destination);
    }

    async resume() {
        const context = this.ensureContext();

        if (! context) {
            return false;
        }

        if (context.state === 'suspended') {
            try {
                await context.resume();
            } catch {
                return false;
            }
        }

        if (context.state === 'running') {
            this.hasUnlockedAudio = true;
            this.flushPendingQueue();
            this.syncDomState();

            return true;
        }

        return false;
    }

    queueSound(name, options = {}) {
        const normalizedName = this.normalizeSoundName(name);

        if (! normalizedName || ! SOUND_LIBRARY[normalizedName]) {
            return;
        }

        this.pendingQueue.push({
            name: normalizedName,
            options: {
                ...options,
                bypassCooldown: true,
                queueIfBlocked: false,
            },
        });

        this.pendingQueue = this.pendingQueue.slice(-12);
    }

    flushPendingQueue() {
        if (! this.pendingQueue.length || ! this.hasUnlockedAudio) {
            return;
        }

        const queuedSounds = [...this.pendingQueue];
        this.pendingQueue = [];

        queuedSounds.forEach(({ name, options }, index) => {
            window.setTimeout(() => {
                this.play(name, {
                    ...options,
                    bypassCooldown: true,
                    queueIfBlocked: false,
                });
            }, index * 90);
        });
    }

    canPlay(name, { bypassCooldown = false } = {}) {
        if (! this.isAudioAllowed) {
            return false;
        }

        const soundName = this.normalizeSoundName(name);
        const sound = SOUND_LIBRARY[soundName];

        if (! sound) {
            return false;
        }

        if (bypassCooldown) {
            return true;
        }

        const now = Date.now();
        const previousTime = this.lastPlayedAt.get(soundName) ?? 0;

        if ((now - previousTime) < sound.cooldown) {
            return false;
        }

        this.lastPlayedAt.set(soundName, now);

        return true;
    }

    play(name, {
        autoplay = false,
        interactive = false,
        bypassCooldown = false,
        queueIfBlocked = true,
    } = {}) {
        const soundName = this.normalizeSoundName(name);
        const sound = SOUND_LIBRARY[soundName];

        if (! sound || ! this.canPlay(soundName, { bypassCooldown })) {
            return false;
        }

        const context = this.ensureContext();

        if (! context) {
            return false;
        }

        if (context.state !== 'running') {
            if (queueIfBlocked && (autoplay || ! interactive)) {
                this.queueSound(soundName, {
                    autoplay,
                    interactive: false,
                });
            }

            void this.resume();

            return false;
        }

        const startAt = context.currentTime + 0.02;

        this.playSequence(sound.notes, startAt, {
            gap: sound.gap,
            intensity: sound.intensity,
        });

        return true;
    }

    playSequence(notes, startAt, { intensity = 1, gap = 0.05 } = {}) {
        let currentTime = startAt;

        notes.forEach(([frequency, duration, type], index) => {
            this.playTone(frequency, currentTime, duration, type, intensity, index);
            currentTime += duration + gap;
        });
    }

    playTone(frequency, startAt, duration, type, intensity = 1, index = 0) {
        const context = this.ensureContext();

        if (! context || ! this.compressor) {
            return;
        }

        const oscillator = context.createOscillator();
        const envelope = context.createGain();
        const lowpass = context.createBiquadFilter();
        const highpass = context.createBiquadFilter();
        const peakVolume = Math.max(0.0001, this.volumeLevel * 0.09 * intensity);

        oscillator.type = type;
        oscillator.frequency.setValueAtTime(frequency, startAt);
        oscillator.detune.setValueAtTime(index % 2 === 0 ? -3 : 3, startAt);

        highpass.type = 'highpass';
        highpass.frequency.setValueAtTime(110, startAt);

        lowpass.type = 'lowpass';
        lowpass.frequency.setValueAtTime(2800, startAt);
        lowpass.Q.setValueAtTime(0.5, startAt);

        envelope.gain.setValueAtTime(0.0001, startAt);
        envelope.gain.linearRampToValueAtTime(peakVolume, startAt + 0.015);
        envelope.gain.exponentialRampToValueAtTime(0.0001, startAt + duration);

        oscillator.connect(highpass);
        highpass.connect(lowpass);
        lowpass.connect(envelope);
        envelope.connect(this.compressor);

        oscillator.start(startAt);
        oscillator.stop(startAt + duration + 0.04);
    }

    syncDomState() {
        const body = document.body;

        body.dataset.audioSoundEnabled = this.soundEnabled ? '1' : '0';
        body.dataset.audioMusicEnabled = this.musicEnabled ? '1' : '0';
        body.dataset.audioVolumeLevel = String(this.volumePercent);
        body.dataset.audioMuted = this.muted ? '1' : '0';

        const effectiveAudioEnabled = this.isAudioAllowed;
        this.statusText = ! this.soundEnabled
            ? 'Audio desactive'
            : (this.muted ? 'Audio coupe' : 'Audio actif');

        document.querySelectorAll('[data-audio-toggle]').forEach((button) => {
            if (!(button instanceof HTMLButtonElement)) {
                return;
            }

            const onLabel = button.dataset.audioLabelOn ?? 'Audio actif';
            const offLabel = button.dataset.audioLabelOff ?? 'Audio coupe';
            const disabledLabel = button.dataset.audioLabelDisabled ?? 'Audio desactive';
            const label = ! this.soundEnabled
                ? disabledLabel
                : (this.muted ? offLabel : onLabel);

            button.dataset.audioState = effectiveAudioEnabled ? 'on' : 'off';
            button.setAttribute('aria-pressed', this.muted ? 'true' : 'false');
            button.setAttribute('aria-label', label);

            button.querySelectorAll('[data-audio-toggle-text]').forEach((node) => {
                node.textContent = label;
            });

            button.querySelectorAll('[data-audio-toggle-indicator]').forEach((node) => {
                node.textContent = effectiveAudioEnabled ? 'On' : 'Off';
            });
        });

        document.querySelectorAll('[data-audio-status-label]').forEach((node) => {
            node.textContent = this.statusText;
        });

        document.dispatchEvent(new CustomEvent('chronomots:audio-state-changed', {
            detail: this.serializePreferences(),
        }));
    }

    bindToggleButtons() {
        document.querySelectorAll('[data-audio-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                if (! this.soundEnabled) {
                    return;
                }

                this.toggleMute();
            });
        });
    }

    bindPreviewButtons() {
        document.querySelectorAll('[data-audio-preview]').forEach((button) => {
            button.addEventListener('click', () => {
                const sound = button.dataset.audioPreview;

                if (! sound) {
                    return;
                }

                void this.resume().then(() => {
                    this.play(sound, {
                        interactive: true,
                        bypassCooldown: true,
                        queueIfBlocked: false,
                    });
                });
            });
        });
    }

    bindAutoplayElements() {
        document.querySelectorAll('[data-audio-autoplay]').forEach((element, index) => {
            if (element.dataset.audioAutoplayHandled === '1') {
                return;
            }

            const sound = element.dataset.audioAutoplay;

            if (! sound) {
                return;
            }

            element.dataset.audioAutoplayHandled = '1';

            window.setTimeout(() => {
                this.play(sound, {
                    autoplay: true,
                    queueIfBlocked: true,
                });
            }, 130 * (index + 1));
        });
    }

    bindPreferencesForm() {
        const preferencesForm = document.querySelector('[data-audio-preferences-form]');
        const volumeInput = preferencesForm?.querySelector('[data-audio-volume-input]');
        const volumeLabel = preferencesForm?.querySelector('[data-audio-volume-label]');

        if (! (preferencesForm instanceof HTMLFormElement)) {
            return;
        }

        const syncPreferences = ({ playPreview = false } = {}) => {
            const soundEnabled = preferencesForm.querySelector('input[name="sound_enabled"]')?.checked ?? true;
            const musicEnabled = preferencesForm.querySelector('input[name="music_enabled"]')?.checked ?? false;
            const volumeLevel = volumeInput?.value ?? 70;

            this.updatePreferences({
                soundEnabled,
                musicEnabled,
                volumeLevel,
                persist: true,
            });

            if (volumeLabel) {
                volumeLabel.textContent = `${volumeLevel}%`;
            }

            if (playPreview) {
                this.play('word-valid', {
                    interactive: true,
                    bypassCooldown: true,
                });
            }
        };

        syncPreferences();

        preferencesForm.addEventListener('change', (event) => {
            const target = event.target;

            syncPreferences({
                playPreview: target instanceof HTMLInputElement && target.type === 'checkbox',
            });
        });

        volumeInput?.addEventListener('input', () => {
            syncPreferences();
        });
    }
}

const initAudioService = () => {
    const { dataset } = document.body;
    const storedPreferences = readStoredPreferences() ?? {};
    const audioService = new AudioService({
        soundEnabled: typeof storedPreferences.soundEnabled === 'boolean'
            ? storedPreferences.soundEnabled
            : boolFromDataset(dataset.audioSoundEnabled, true),
        musicEnabled: typeof storedPreferences.musicEnabled === 'boolean'
            ? storedPreferences.musicEnabled
            : boolFromDataset(dataset.audioMusicEnabled, false),
        muted: typeof storedPreferences.muted === 'boolean'
            ? storedPreferences.muted
            : boolFromDataset(dataset.audioMuted, false),
        volumeLevel: storedPreferences.volumeLevel ?? dataset.audioVolumeLevel ?? 70,
    });

    document.addEventListener('chronomots:play-sound', (event) => {
        const sound = event.detail?.sound;

        if (typeof sound === 'string') {
            audioService.play(sound, {
                interactive: false,
            });
        }
    });

    audioService.bindToggleButtons();
    audioService.bindPreviewButtons();
    audioService.bindPreferencesForm();
    audioService.bindAutoplayElements();
    audioService.syncDomState();

    window.ChronomotsAudio = audioService;

    return audioService;
};

export { initAudioService };
