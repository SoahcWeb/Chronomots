<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Résultat Lettres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Partie terminée pour {{ $ageGroup->name }}
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Voici le résultat de cette première manche solo. Tu peux rejouer immédiatement avec un nouveau tirage.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        @php
            $performanceLabel = $score >= 80 ? 'Excellent mot' : ($score >= 50 ? 'Belle tentative' : 'Premier essai');
            $performanceBadge = $score >= 80 ? 'chronomots-badge--success' : ($score >= 50 ? 'chronomots-badge--info' : 'chronomots-badge--warning');
        @endphp

        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="chronomots-panel chronomots-result-shell rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <span class="chronomots-badge {{ $performanceBadge }}">{{ $performanceLabel }}</span>
                    <span class="chronomots-live-pill">Résultat enregistré</span>
                </div>

                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="chronomots-score-burst rounded-[1.75rem] bg-gradient-to-br from-cyan-100 via-white to-sky-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Mot soumis</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $submittedWord }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Validation par tirage et dictionnaire de test.</p>
                    </div>
                    <div class="chronomots-score-burst rounded-[1.75rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Score</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $score }} pts</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Longueur du mot multipliée par 10 pour cette V1 solo.</p>
                    </div>
                </div>

                <div class="mt-8 rounded-[1.75rem] border border-white/70 bg-white/55 p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Tirage utilisé</p>
                            <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Tes lettres de cette manche</h3>
                        </div>
                        <span class="chronomots-pill">{{ count($letters) }} lettres</span>
                    </div>

                    <div class="mt-5 grid gap-3 grid-cols-3 sm:grid-cols-4 lg:grid-cols-5">
                        @foreach ($letters as $letter)
                            <div class="chronomots-soft-card chronomots-token chronomots-token--letters flex min-h-18 items-center justify-center rounded-[1.4rem] px-3 py-4">
                                <span class="text-2xl font-black tracking-[-0.05em] text-slate-950">{{ $letter }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="chronomots-kicker">Résumé de session</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Partie enregistrée</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Ton essai est sauvegardé et peut nourrir tes prochaines statistiques joueur.
                </p>

                <div class="mt-6 space-y-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Type</p>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <p class="text-lg font-bold text-slate-950">Lettres</p>
                            <span class="chronomots-badge chronomots-badge--info">Solo</span>
                        </div>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Statut</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $gameSession->status }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mot enregistré</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $letterRound->submitted_word }}</p>
                    </div>
                </div>

                <div class="mt-6 chronomots-mini-grid">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Catégorie</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $ageGroup->name }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Performance</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $performanceLabel }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <a href="{{ route('play.letters.show', $ageGroup) }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                        Rejouer
                    </a>
                    <a href="{{ route('play') }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                        Retour aux modes
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
