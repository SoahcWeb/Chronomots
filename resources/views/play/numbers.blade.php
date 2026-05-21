<x-app-layout>
    @php
        $opponentLevel = $opponentLevel ?? null;
        $opponentLevelLabel = $opponentLevelLabel ?? null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700">Mode Chiffres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $ageGroup->name }} : approche la cible au plus juste
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Utilise uniquement les nombres affichés pour écrire un calcul valide. Cette V1 accepte les opérations `+`, `-`, `*`, `/` et les parenthèses.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <section x-data="chronomotsTimer({{ $timerSeconds }})" class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <span class="chronomots-badge chronomots-badge--plum">Tirage en cours</span>
                            <span class="chronomots-live-pill">Mode chiffres</span>
                            @if ($opponentLevelLabel)
                                <span class="chronomots-live-pill">VS IA {{ $opponentLevelLabel }}</span>
                            @endif
                        </div>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Atteins la cible {{ $targetNumber }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="chronomots-pill">{{ $ageGroup->name }}</span>
                            <span class="chronomots-pill">{{ $timerSeconds }} secondes</span>
                            <span class="chronomots-pill">Objectif : viser juste</span>
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
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Chrono chiffres</p>
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

                <div class="mt-8 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div class="grid gap-3 grid-cols-2 {{ count($numbers) >= 6 ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                        @foreach ($numbers as $number)
                            <div class="chronomots-soft-card chronomots-token chronomots-token--numbers flex min-h-20 items-center justify-center rounded-[1.6rem] px-4 py-5 text-center shadow-sm">
                                <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $number }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-[1.8rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 px-6 py-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Cible</p>
                        <p class="mt-2 text-4xl font-black tracking-[-0.05em] text-slate-950">{{ $targetNumber }}</p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('play.numbers.submit', $ageGroup) }}"
                    :class="{ 'chronomots-form-disabled': expired }"
                    class="chronomots-form-shell mt-8 space-y-4 rounded-[1.75rem] p-5 sm:p-6"
                    data-feedback-reveal
                >
                    @csrf
                    <input type="hidden" name="draw_id" value="{{ $drawId }}">
                    <input type="hidden" name="opponent_level" value="{{ $opponentLevel }}">

                    <div>
                        <label for="submitted_solution" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Écrire un calcul
                        </label>
                        <input
                            id="submitted_solution"
                            name="submitted_solution"
                            type="text"
                            maxlength="255"
                            value="{{ old('submitted_solution', $submittedSolution) }}"
                            :disabled="expired"
                            class="chronomots-input mt-3 block w-full rounded-[1.4rem] px-5 py-4 text-lg font-semibold tracking-[0.02em] text-slate-950 shadow-sm outline-none"
                            placeholder="Exemple : (25 + 10) * 4"
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
                            Valider mon calcul
                        </button>
                        <a href="{{ route('play.numbers.show', ['ageGroup' => $ageGroup, 'opponent_level' => $opponentLevel]) }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Nouveau tirage
                        </a>
                    </div>
                </form>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="chronomots-kicker">Barème V1</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Simple et lisible</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Le scoring récompense la précision, tout en gardant une lecture immédiate pour chaque âge.
                </p>

                <div class="mt-6 space-y-3">
                    @if ($opponentLevelLabel)
                        <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-950">Duel activé</p>
                                <span class="chronomots-badge chronomots-badge--plum">VS IA</span>
                            </div>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Tu affrontes une IA de niveau {{ strtolower($opponentLevelLabel) }} sur la même cible.</p>
                        </div>
                    @endif
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Exact</p>
                            <span class="chronomots-badge chronomots-badge--success">100 pts</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Si ton résultat atteint exactement la cible : 100 points.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Très proche</p>
                            <span class="chronomots-badge chronomots-badge--info">50 pts</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Si l’écart est inférieur ou égal à 5 : 50 points.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-slate-950">Proche</p>
                            <span class="chronomots-badge chronomots-badge--warning">25 pts</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Si l’écart est inférieur ou égal à 10 : 25 points, sinon 0.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
