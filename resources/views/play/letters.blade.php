<x-app-layout>
    @php
        $opponentLevel = $opponentLevel ?? null;
        $opponentLevelLabel = $opponentLevelLabel ?? null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Mode Lettres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $ageGroup->name }} : trouve le meilleur mot possible
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Utilise uniquement les lettres affichées. Pour cette première version, le score dépend simplement de la longueur du mot proposé.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <section x-data="chronomotsTimer({{ $timerSeconds }})" class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <span class="chronomots-badge chronomots-badge--info">Tirage en cours</span>
                            <span class="chronomots-live-pill">Mode lettres</span>
                            @if ($opponentLevelLabel)
                                <span class="chronomots-live-pill">VS IA {{ $opponentLevelLabel }}</span>
                            @endif
                        </div>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Compose un mot avec {{ $lettersCount }} lettres disponibles
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="chronomots-pill">{{ $ageGroup->name }}</span>
                            <span class="chronomots-pill">{{ $timerSeconds }} secondes</span>
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
                        class="chronomots-soft-card chronomots-timer rounded-[1.5rem] p-5 sm:w-48"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Chrono lettres</p>
                        <p class="chronomots-timer-value mt-3 text-4xl font-black tracking-[-0.05em] text-slate-950">
                            <span x-text="minutes"></span>:<span x-text="seconds"></span>
                        </p>
                        <p x-show="!expired" class="mt-2 text-sm leading-6 text-slate-600">
                            Temps prévu pour {{ $ageGroup->name }}.
                        </p>
                        <p x-show="expired" class="mt-2 text-sm font-semibold leading-6 text-rose-600">
                            Temps écoulé
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 grid-cols-3 {{ $lettersCount >= 10 ? 'sm:grid-cols-5' : 'sm:grid-cols-4' }}">
                    @foreach ($letters as $letter)
                        <div class="chronomots-soft-card chronomots-token chronomots-token--letters flex min-h-20 items-center justify-center rounded-[1.6rem] px-4 py-5 text-center shadow-sm">
                            <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $letter }}</span>
                        </div>
                    @endforeach
                </div>

                <form
                    method="POST"
                    action="{{ route('play.letters.submit', $ageGroup) }}"
                    :class="{ 'chronomots-form-disabled': expired }"
                    class="chronomots-form-shell mt-8 space-y-4 rounded-[1.75rem] p-5 sm:p-6"
                    data-feedback-reveal
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
                            :disabled="expired"
                            class="chronomots-input mt-3 block w-full rounded-[1.4rem] px-5 py-4 text-lg font-semibold uppercase tracking-[0.08em] text-slate-950 shadow-sm outline-none"
                            placeholder="Entre ton mot"
                            autocomplete="off"
                        >

                        @if (! empty($errorMessage))
                            <p class="chronomots-inline-feedback mt-3 text-sm font-medium text-rose-600" data-feedback-error data-audio-autoplay="error">{{ $errorMessage }}</p>
                        @endif
                    </div>

                    <p x-show="expired" class="text-sm font-semibold text-rose-600">
                        Temps écoulé. Le formulaire est maintenant désactivé.
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button
                            type="submit"
                            :disabled="expired"
                            :class="{ 'cursor-not-allowed opacity-60 hover:translate-y-0': expired }"
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
                <p class="chronomots-kicker">Règles de cette V1</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Simple, rapide et jouable</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Un cadre clair pour jouer vite, apprendre les réflexes du jeu et préparer les prochaines évolutions.
                </p>

                <div class="mt-6 space-y-3">
                    @if ($opponentLevelLabel)
                        <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-950">Duel activé</p>
                                <span class="chronomots-badge chronomots-badge--plum">VS IA</span>
                            </div>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Tu affrontes une IA de niveau {{ strtolower($opponentLevelLabel) }} sur le même tirage.</p>
                        </div>
                    @endif
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Tirage équilibré</p>
                            <span class="chronomots-badge chronomots-badge--info">Base</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le mélange contient voyelles et consonnes, avec un nombre total adapté à l’âge.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Validation serveur</p>
                            <span class="chronomots-badge chronomots-badge--success">Sécurisé</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le mot est accepté seulement s’il utilise uniquement les lettres disponibles.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Score immédiat</p>
                            <span class="chronomots-badge chronomots-badge--warning">Rapide</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le score correspond à la longueur du mot multipliée par 10.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
