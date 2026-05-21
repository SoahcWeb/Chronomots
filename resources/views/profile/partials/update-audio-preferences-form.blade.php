<section>
    <header>
        <h2 class="text-lg font-bold text-slate-950">
            Ambiance audio
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-600">
            Active les sons de validation, d’erreur et de progression, puis ajuste leur intensité selon ton confort.
        </p>
    </header>

    <form
        method="post"
        action="{{ route('profile.preferences.update') }}"
        class="mt-6 space-y-6"
        data-audio-preferences-form
    >
        @csrf
        @method('patch')

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="chronomots-soft-card flex items-start gap-3 rounded-[1.4rem] p-4">
                <input
                    type="checkbox"
                    name="sound_enabled"
                    value="1"
                    @checked(old('sound_enabled', $preferences->sound_enabled))
                    class="mt-1 rounded border-slate-300 text-cyan-600 shadow-sm focus:ring-cyan-400"
                >
                <span>
                    <span class="block text-sm font-semibold text-slate-950">Effets sonores</span>
                    <span class="mt-1 block text-sm leading-6 text-slate-600">Validation, erreur, chrono faible, victoire et succès débloqués.</span>
                </span>
            </label>

            <label class="chronomots-soft-card flex items-start gap-3 rounded-[1.4rem] p-4">
                <input
                    type="checkbox"
                    name="music_enabled"
                    value="1"
                    @checked(old('music_enabled', $preferences->music_enabled))
                    class="mt-1 rounded border-slate-300 text-cyan-600 shadow-sm focus:ring-cyan-400"
                >
                <span>
                    <span class="block text-sm font-semibold text-slate-950">Ambiance musicale</span>
                    <span class="mt-1 block text-sm leading-6 text-slate-600">Préférence enregistrée pour les futures ambiances sonores de Chronomots.</span>
                </span>
            </label>
        </div>

        <div class="chronomots-form-shell rounded-[1.4rem] p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <label for="volume_level" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                        Volume
                    </label>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        Réglage global des sons de jeu sur mobile et desktop.
                    </p>
                </div>
                <span class="chronomots-pill" data-audio-volume-label>{{ old('volume_level', $preferences->volume_level) }}%</span>
            </div>

            <input
                id="volume_level"
                name="volume_level"
                type="range"
                min="0"
                max="100"
                step="5"
                value="{{ old('volume_level', $preferences->volume_level) }}"
                class="mt-4 block w-full accent-cyan-600"
                data-audio-volume-input
            >
            <x-input-error class="mt-2" :messages="$errors->get('volume_level')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Enregistrer l’audio</x-primary-button>

            @if (session('status') === 'audio-preferences-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="text-sm text-slate-600"
                >Préférences audio enregistrées.</p>
            @endif
        </div>
    </form>
</section>
