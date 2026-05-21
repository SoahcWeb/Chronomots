<x-app-layout>
    @php
        $submittedWord = $submittedWord ?? '';
        $errorMessage = $errorMessage ?? null;
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Défi quotidien Lettres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $challenge->challenge_date->format('d/m/Y') }} : même tirage pour tous
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Une seule tentative aujourd’hui. Le tirage est stocké côté serveur et partagé avec tous les joueurs.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <section x-data='chronomotsTimer({ initialSeconds: {{ $initialRemainingSeconds ?? $payload['timer_seconds'] }}, expiresAt: @json($expiresAtIso ?? null) })' class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap gap-2">
                            <span class="chronomots-badge chronomots-badge--info">Défi du jour</span>
                            <span class="chronomots-live-pill">Mode lettres</span>
                        </div>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Compose ton meilleur mot
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
                        <p x-show="expired" class="mt-2 text-sm font-semibold leading-6 text-rose-600">
                            Temps écoulé
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-4 gap-3 sm:grid-cols-4">
                    @foreach ($payload['letters'] as $letter)
                        <div class="chronomots-soft-card chronomots-token chronomots-token--letters flex min-h-20 items-center justify-center rounded-[1.6rem] px-4 py-5 text-center shadow-sm">
                            <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $letter }}</span>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('daily-challenges.submit', $challenge) }}" :class="{ 'chronomots-form-disabled': expired }" class="chronomots-form-shell mt-8 space-y-4 rounded-[1.75rem] p-5 sm:p-6" data-feedback-reveal>
                    @csrf

                    <div>
                        <label for="submitted_word" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Ton mot du jour
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
                    Le meilleur score du jour se met à jour au fil des tentatives. Une seule participation par joueur.
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
                        <p class="font-semibold text-slate-950">Anti-triche léger</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le tirage vit en base et le score est recalculé côté serveur, sans dépendre d’une session PHP.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
