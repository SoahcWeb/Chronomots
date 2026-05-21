<x-app-layout>
    @php
        $unlockedAchievements = $unlockedAchievements ?? collect();
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700">Résultat Chiffres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Partie terminée pour {{ $ageGroup->name }}
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                La partie a été enregistrée. Tu peux rejouer tout de suite avec un nouveau tirage.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        @php
            $performanceLabel = $difference === 0 ? 'Cible atteinte' : ($difference <= 5 ? 'Très proche' : ($difference <= 10 ? 'Bonne approche' : 'À retenter'));
            $performanceBadge = $difference === 0 ? 'chronomots-badge--success' : ($difference <= 5 ? 'chronomots-badge--info' : ($difference <= 10 ? 'chronomots-badge--plum' : 'chronomots-badge--warning'));
            $duelBadge = match ($duelOutcome) {
                'Victoire' => 'chronomots-badge--success',
                'Défaite' => 'chronomots-badge--warning',
                'Égalité' => 'chronomots-badge--info',
                default => null,
            };
            $pageSound = match (true) {
                $duelOutcome === 'Victoire' => 'victory',
                $duelOutcome === 'Défaite' => 'defeat',
                default => 'valid',
            };
        @endphp

        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="chronomots-panel chronomots-result-shell rounded-[2rem] p-6 sm:p-8" data-audio-autoplay="{{ $pageSound }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="chronomots-badge {{ $performanceBadge }}">{{ $performanceLabel }}</span>
                        @if ($opponentResult && $duelBadge)
                            <span class="chronomots-badge {{ $duelBadge }}">{{ $duelOutcome }}</span>
                        @endif
                    </div>
                    <span class="chronomots-live-pill">Résultat enregistré</span>
                </div>

                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <x-player-avatar :avatar="$playerAvatar" :title="'Toi'" :subtitle="$playerAvatar['name']" size="lg" />
                    @if ($opponentAvatar)
                        <x-player-avatar :avatar="$opponentAvatar" :title="$opponentAvatar['name']" :subtitle="$opponentResult['quality_label']" size="lg" />
                    @endif
                </div>

                <div class="mt-6 grid gap-4 {{ $opponentResult ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }}">
                    <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 p-5 shadow-sm" data-feedback-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Cible</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $targetNumber }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Objectif à atteindre avec les nombres tirés.</p>
                    </div>
                    <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-cyan-100 via-white to-sky-50 p-5 shadow-sm" data-feedback-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Résultat</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $resultValue }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Valeur produite par ton calcul.</p>
                    </div>
                    <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-orange-100 via-white to-amber-50 p-5 shadow-sm" data-feedback-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Score</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $score }} pts</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Calcul du score selon la proximité avec la cible.</p>
                    </div>
                    @if ($opponentResult)
                        <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-violet-100 via-white to-indigo-50 p-5 shadow-sm" data-feedback-reveal>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">IA {{ $opponentLevelLabel }}</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $opponentResult['score'] }} pts</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $opponentResult['quality_label'] }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-8 space-y-4">
                    <div class="chronomots-form-shell rounded-[1.5rem] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Calcul soumis</p>
                        <p class="mt-3 text-lg font-bold text-slate-950">{{ $submittedSolution }}</p>
                    </div>

                    <div class="chronomots-form-shell rounded-[1.5rem] p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Écart avec la cible</p>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p class="text-lg font-bold text-slate-950">{{ $difference }}</p>
                            <span class="chronomots-pill">{{ $performanceLabel }}</span>
                        </div>
                    </div>

                    @if ($opponentResult)
                        <div class="chronomots-form-shell rounded-[1.5rem] p-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Calcul IA</p>
                            <p class="mt-3 text-lg font-bold text-slate-950">{{ $opponentResult['submitted_solution'] }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Résultat {{ $opponentResult['result_value'] }} • Écart {{ $opponentResult['difference'] }}</p>
                        </div>
                    @endif

                    <div class="rounded-[1.75rem] border border-white/70 bg-white/55 p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Nombres utilisés</p>
                                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Ton tirage chiffres</h3>
                            </div>
                            <span class="chronomots-pill">{{ count($numbers) }} nombres</span>
                        </div>

                        <div class="mt-5 grid gap-3 grid-cols-2 {{ count($numbers) >= 6 ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                        @foreach ($numbers as $number)
                            <div class="chronomots-soft-card chronomots-token chronomots-token--numbers flex min-h-18 items-center justify-center rounded-[1.4rem] px-3 py-4">
                                <span class="text-2xl font-black tracking-[-0.05em] text-slate-950">{{ $number }}</span>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="chronomots-kicker">Résumé de session</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Partie enregistrée</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Cette manche vient enrichir ton historique personnel et tes meilleurs scores.
                </p>

                <div class="mt-6 space-y-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Type</p>
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <p class="text-lg font-bold text-slate-950">Chiffres</p>
                            <span class="chronomots-badge chronomots-badge--plum">{{ $opponentResult ? 'VS IA' : 'Solo' }}</span>
                        </div>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Statut</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $gameSession->status }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Calcul enregistré</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $numberRound->submitted_solution }}</p>
                    </div>
                    @if ($opponentResult)
                        <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Score IA</p>
                            <div class="mt-2">
                                <x-player-avatar :avatar="$opponentAvatar" :title="$opponentAvatar['name']" :subtitle="$opponentResult['score'].' pts'" size="sm" />
                            </div>
                        </div>
                    @endif
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

                @if ($opponentResult)
                    <div class="mt-6 chronomots-form-shell rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Comparaison joueur vs IA</p>
                        <div class="mt-2 flex items-center justify-between gap-4">
                            <p class="text-sm font-semibold text-slate-950">Toi: {{ $score }} pts</p>
                            <p class="text-sm font-semibold text-slate-950">IA: {{ $opponentResult['score'] }} pts</p>
                            <span class="chronomots-pill">{{ $duelOutcome }}</span>
                        </div>
                    </div>
                @endif

                @if ($unlockedAchievements->isNotEmpty())
                    <div class="mt-6 rounded-[1.6rem] border border-emerald-200/80 bg-emerald-50/85 p-4 chronomots-achievement-burst" data-audio-autoplay="achievement" data-feedback-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Succès débloqués</p>
                        <div class="mt-3 space-y-3">
                            @foreach ($unlockedAchievements as $achievement)
                                <div class="flex items-start gap-3 rounded-[1.2rem] bg-white/85 px-4 py-3 shadow-sm">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-[1rem] bg-emerald-100 text-sm font-black text-emerald-800">
                                        {{ $achievement->icon }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-950">{{ $achievement->name }}</p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $achievement->description }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-6 flex flex-col gap-3">
                    <a href="{{ route('play.numbers.show', ['ageGroup' => $ageGroup, 'opponent_level' => $opponentLevel]) }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
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
