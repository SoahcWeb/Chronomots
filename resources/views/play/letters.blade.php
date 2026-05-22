<x-app-layout>
    @php
        $opponentLevel = $opponentLevel ?? null;
        $opponentLevelLabel = $opponentLevelLabel ?? null;
        $allowedChoices = $allowedChoices ?? ['vowel', 'consonant'];
        $drawChoiceHistory = $drawChoiceHistory ?? [];
        $revealedLettersCount = $revealedLettersCount ?? count($letters);
        $remainingLettersCount = $remainingLettersCount ?? max(0, $lettersCount - $revealedLettersCount);
        $drawCompleted = $drawCompleted ?? false;
        $latestLetter = $latestLetter ?? null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Mode Lettres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $ageGroup->name }} : construis ton tirage, puis trouve le meilleur mot
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Choisis voyelle ou consonne à chaque étape. Chronomots révèle une lettre pondérée à chaque clic, tout en gardant un tirage jouable.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <section x-data='chronomotsTimer({ initialSeconds: {{ $initialRemainingSeconds ?? $timerSeconds }}, expiresAt: @json($expiresAtIso ?? null) })' class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <span class="chronomots-badge chronomots-badge--info">Tirage interactif</span>
                            <span class="chronomots-live-pill">Mode lettres</span>
                            @if ($opponentLevelLabel)
                                <span class="chronomots-live-pill">VS IA {{ $opponentLevelLabel }}</span>
                            @endif
                        </div>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            {{ $drawCompleted ? 'Tirage prêt : propose maintenant ton meilleur mot' : 'Choisis la prochaine lettre' }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="chronomots-pill">{{ $ageGroup->name }}</span>
                            <span class="chronomots-pill">{{ $timerSeconds }} secondes</span>
                            <span class="chronomots-pill">{{ $remainingLettersCount }} lettre{{ $remainingLettersCount > 1 ? 's' : '' }} restante{{ $remainingLettersCount > 1 ? 's' : '' }}</span>
                            <span class="chronomots-pill">Score = longueur x 10</span>
                            @if ($opponentLevelLabel)
                                <span class="chronomots-pill">Adversaire {{ $opponentLevelLabel }}</span>
                            @endif
                        </div>
                    </div>

                    <div
                        :class="{
                            'chronomots-timer--urgent': isUrgent,
                            'chronomots-timer--expired': expired
                        }"
                        data-feedback-timer
                        class="chronomots-soft-card chronomots-timer rounded-[1.5rem] p-5 sm:w-48"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Chrono lettres</p>
                        <p class="chronomots-timer-value mt-3 text-4xl font-black tracking-[-0.05em] text-slate-950">
                            <span x-text="minutes"></span>:<span x-text="seconds"></span>
                        </p>
                        <p x-show="!expired" class="mt-2 text-sm leading-6 text-slate-600">
                            Le chrono tourne pendant tout le tirage.
                        </p>
                        <p x-show="expired" class="mt-2 text-sm font-semibold leading-6 text-rose-600">
                            Temps écoulé
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 grid-cols-3 {{ $lettersCount >= 10 ? 'sm:grid-cols-5' : 'sm:grid-cols-4' }}">
                    @for ($index = 0; $index < $lettersCount; $index++)
                        @php
                            $letter = $letters[$index] ?? null;
                            $isLatestLetter = $letter !== null && $index === ($revealedLettersCount - 1) && $latestLetter !== null;
                        @endphp

                        <div
                            class="chronomots-soft-card chronomots-token chronomots-token--letters {{ $letter ? 'chronomots-token--revealed' : 'chronomots-token--placeholder' }} {{ $isLatestLetter ? 'chronomots-token--fresh' : '' }} flex min-h-20 items-center justify-center rounded-[1.6rem] px-4 py-5 text-center shadow-sm"
                            @if ($letter) data-feedback-token="{{ $isLatestLetter ? 'fresh' : 'revealed' }}" data-feedback-delay="{{ $index * 45 }}" @endif
                            @if ($isLatestLetter) data-audio-autoplay="letter-reveal" @endif
                        >
                            @if ($letter)
                                <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $letter }}</span>
                            @else
                                <span class="text-lg font-semibold uppercase tracking-[0.18em] text-slate-300">?</span>
                            @endif
                        </div>
                    @endfor
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Séquence de choix</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($drawChoiceHistory as $choice)
                                <span class="chronomots-pill">{{ $choice === 'vowel' ? 'Voyelle' : 'Consonne' }}</span>
                            @empty
                                <span class="text-sm text-slate-500">Aucun choix enregistré pour l’instant.</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-[1.8rem] bg-gradient-to-br from-cyan-100 via-white to-emerald-50 px-6 py-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Progression</p>
                        <p class="mt-2 text-4xl font-black tracking-[-0.05em] text-slate-950">{{ $revealedLettersCount }}/{{ $lettersCount }}</p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('play.letters.draw', $ageGroup) }}"
                    :class="{ 'chronomots-form-disabled': expired || {{ $drawCompleted ? 'true' : 'false' }} }"
                    class="chronomots-form-shell mt-8 space-y-4 rounded-[1.75rem] p-5 sm:p-6"
                    data-feedback-submit
                    data-feedback-submit-sound="letter-reveal"
                >
                    @csrf
                    <input type="hidden" name="draw_id" value="{{ $drawId }}">
                    <input type="hidden" name="opponent_level" value="{{ $opponentLevel }}">

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="submit"
                            name="letter_type"
                            value="vowel"
                            :disabled="expired || {{ in_array('vowel', $allowedChoices, true) ? 'false' : 'true' }} || {{ $drawCompleted ? 'true' : 'false' }}"
                            onclick="document.dispatchEvent(new CustomEvent('chronomots:play-sound', { detail: { sound: 'letter-reveal' } }))"
                            class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                        >
                            Choisir une voyelle
                        </button>
                        <button
                            type="submit"
                            name="letter_type"
                            value="consonant"
                            :disabled="expired || {{ in_array('consonant', $allowedChoices, true) ? 'false' : 'true' }} || {{ $drawCompleted ? 'true' : 'false' }}"
                            onclick="document.dispatchEvent(new CustomEvent('chronomots:play-sound', { detail: { sound: 'letter-reveal' } }))"
                            class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                        >
                            Choisir une consonne
                        </button>
                    </div>
                </form>

                <form
                    method="POST"
                    action="{{ route('play.letters.submit', $ageGroup) }}"
                    :class="{ 'chronomots-form-disabled': expired || ! {{ $drawCompleted ? 'true' : 'false' }} }"
                    class="chronomots-form-shell mt-4 space-y-4 rounded-[1.75rem] p-5 sm:p-6"
                    data-feedback-reveal
                    data-feedback-submit
                    data-feedback-submit-sound="word-valid"
                >
                    @csrf
                    <input type="hidden" name="draw_id" value="{{ $drawId }}">
                    <input type="hidden" name="opponent_level" value="{{ $opponentLevel }}">

                    <div>
                        <label for="submitted_word" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Proposer un mot
                        </label>
                        <input
                            id="submitted_word"
                            name="submitted_word"
                            type="text"
                            maxlength="32"
                            value="{{ old('submitted_word', $submittedWord) }}"
                            :disabled="expired || ! {{ $drawCompleted ? 'true' : 'false' }}"
                            class="chronomots-input mt-3 block w-full rounded-[1.4rem] px-5 py-4 text-lg font-semibold uppercase tracking-[0.08em] text-slate-950 shadow-sm outline-none"
                            placeholder="{{ $drawCompleted ? 'Entre ton mot' : 'Complète d’abord le tirage' }}"
                            autocomplete="off"
                        >

                        @if (! empty($errorMessage))
                            <p class="chronomots-inline-feedback mt-3 text-sm font-medium text-rose-600" data-feedback-error data-audio-autoplay="error">{{ $errorMessage }}</p>
                        @endif
                    </div>

                    <p x-show="expired" class="text-sm font-semibold text-rose-600">
                        Temps écoulé. Le serveur refusera maintenant toute soumission pour ce tirage.
                    </p>

                    @if (! $drawCompleted)
                        <p class="text-sm font-semibold text-slate-600">
                            Révèle encore {{ $remainingLettersCount }} lettre{{ $remainingLettersCount > 1 ? 's' : '' }} avant la validation du mot.
                        </p>
                    @endif

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="submit"
                            :disabled="expired || ! {{ $drawCompleted ? 'true' : 'false' }}"
                            :class="{ 'cursor-not-allowed opacity-60 hover:translate-y-0': expired || ! {{ $drawCompleted ? 'true' : 'false' }} }"
                            class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                        >
                            Valider mon mot
                        </button>
                        <a href="{{ route('play.letters.show', ['ageGroup' => $ageGroup, 'opponent_level' => $opponentLevel]) }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Nouveau tirage
                        </a>
                    </div>
                </form>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="chronomots-kicker">Tirage moderne</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Interactif, rapide et contrôlé</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Tu pilotes la construction du tirage, pendant que Chronomots applique les pondérations et garde un équilibre jouable en arrière-plan.
                </p>

                <div class="mt-6 space-y-3">
                    @if ($opponentLevelLabel)
                        <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-950">Duel activé</p>
                                <span class="chronomots-badge chronomots-badge--plum">VS IA</span>
                            </div>
                            <p class="mt-1 text-sm leading-6 text-slate-600">L’IA attend la fin du tirage complet avant de jouer sur les mêmes lettres que toi.</p>
                        </div>
                    @endif
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Voyelles garanties</p>
                            <span class="chronomots-badge chronomots-badge--info">3 à 6</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le moteur empêche les choix qui casseraient l’équilibre voyelles/consonnes du tirage.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Contraintes serveur</p>
                            <span class="chronomots-badge chronomots-badge--success">Sécurisé</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Les lettres révélées, le chrono et le score restent validés côté serveur à chaque étape.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Solvabilité visée</p>
                            <span class="chronomots-badge chronomots-badge--warning">6–9 lettres</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">La pondération moderne et un mot graine servent à favoriser des solutions riches sans scanner tout le dictionnaire.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
