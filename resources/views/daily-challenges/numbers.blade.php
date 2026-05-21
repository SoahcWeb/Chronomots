<x-app-layout>
    @php
        $submittedSolution = $submittedSolution ?? '';
        $errorMessage = $errorMessage ?? null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700">Défi quotidien Chiffres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $challenge->challenge_date->format('d/m/Y') }} : même cible pour tous
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Une seule tentative aujourd’hui, même cible pour tous les joueurs, et calcul vérifié côté serveur.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <section x-data='chronomotsTimer({ initialSeconds: {{ $initialRemainingSeconds ?? $payload['timer_seconds'] }}, expiresAt: @json($expiresAtIso ?? null) })' class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <span class="chronomots-badge chronomots-badge--success">Défi du jour</span>
                            <span class="chronomots-live-pill">Mode chiffres</span>
                        </div>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Atteins la cible {{ $payload['target_number'] }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="chronomots-pill">{{ $ageGroup->name }}</span>
                            <span class="chronomots-pill">{{ $payload['timer_seconds'] }} secondes</span>
                            <span class="chronomots-pill">Tentative unique</span>
                        </div>
                    </div>

                    <div
                        :class="{
                            'chronomots-timer--urgent': isUrgent,
                            'chronomots-timer--expired': expired
                        }"
                        class="chronomots-soft-card chronomots-timer rounded-[1.5rem] p-5 sm:w-48"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Chrono du jour</p>
                        <p class="chronomots-timer-value mt-3 text-4xl font-black tracking-[-0.05em] text-slate-950">
                            <span x-text="minutes"></span>:<span x-text="seconds"></span>
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ($payload['numbers'] as $number)
                            <div class="chronomots-soft-card chronomots-token chronomots-token--numbers flex min-h-20 items-center justify-center rounded-[1.6rem] px-4 py-5 text-center shadow-sm">
                                <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $number }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-[1.8rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 px-6 py-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Cible</p>
                        <p class="mt-2 text-4xl font-black tracking-[-0.05em] text-slate-950">{{ $payload['target_number'] }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('daily-challenges.submit', $challenge) }}" :class="{ 'chronomots-form-disabled': expired }" class="chronomots-form-shell mt-8 space-y-4 rounded-[1.75rem] p-5 sm:p-6" data-feedback-reveal>
                    @csrf

                    <div>
                        <label for="submitted_solution" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Ton calcul du jour
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
                        Temps écoulé. Le serveur refusera maintenant toute tentative sur ce défi.
                    </p>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit" :disabled="expired" :class="{ 'cursor-not-allowed opacity-60 hover:translate-y-0': expired }" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Valider ma tentative
                        </button>
                        <a href="{{ route('daily-challenges.index') }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Retour aux défis
                        </a>
                    </div>
                </form>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="chronomots-kicker">Classement du jour</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Repères rapides</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Même cible pour tous, score calculé côté serveur et une seule participation par joueur.
                </p>

                <div class="mt-6 space-y-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur score du jour</p>
                        <p class="mt-2 text-3xl font-black text-slate-950">{{ $bestScoreOfDay }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tentatives enregistrées</p>
                        <p class="mt-2 text-3xl font-black text-slate-950">{{ $attemptsCount }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Score parfait</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Atteindre exactement la cible débloque le badge parfait du jour.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
