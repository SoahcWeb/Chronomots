<x-app-layout>
    @php
        $unlockedAchievements = $unlockedAchievements ?? collect();
        $payload = $challenge->payload;
        $isLetters = $challenge->game_type === 'letters';
        $pageSound = $attempt->is_perfect || $isCurrentUserBest ? 'victory' : 'valid';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] {{ $isLetters ? 'text-cyan-700' : 'text-emerald-700' }}">
                    Résultat du défi quotidien
                </p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $challenge->challenge_date->format('d/m/Y') }} • {{ $isLetters ? 'Lettres' : 'Chiffres' }}
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Ta tentative est enregistrée. Le classement du jour se met à jour à mesure que les joueurs complètent le même défi.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="chronomots-panel chronomots-result-shell rounded-[2rem] p-6 sm:p-8" data-audio-autoplay="{{ $pageSound }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="chronomots-badge {{ $attempt->is_perfect ? 'chronomots-badge--success' : 'chronomots-badge--info' }}">
                            {{ $attempt->is_perfect ? 'Score parfait' : 'Défi enregistré' }}
                        </span>
                        @if ($isCurrentUserBest)
                            <span class="chronomots-badge chronomots-badge--success">Meilleur score du jour</span>
                        @endif
                    </div>
                    <span class="chronomots-live-pill">Classement quotidien actif</span>
                </div>

                <div class="mt-6 flex items-center justify-between gap-4">
                    <x-player-avatar :avatar="$playerAvatar" :title="'Toi'" :subtitle="'Tentative du jour'" size="lg" />
                    <div class="rounded-[1.5rem] bg-white/85 px-5 py-4 text-center shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ton score</p>
                        <p class="mt-2 text-3xl font-black text-slate-950">{{ $attempt->score }}</p>
                    </div>
                </div>

                @if ($isLetters)
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-cyan-100 via-white to-sky-50 p-5 shadow-sm" data-feedback-reveal>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Mot soumis</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $attempt->submitted_word }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Score = longueur x 10 sur ce défi.</p>
                        </div>
                        <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 p-5 shadow-sm" data-feedback-reveal>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Badge parfait</p>
                            <p class="mt-3 text-2xl font-black tracking-[-0.05em] text-slate-950">
                                {{ $attempt->is_perfect ? 'Débloqué' : 'Pas encore' }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Le badge parfait du jour récompense un score égal au meilleur niveau du tirage.</p>
                        </div>
                    </div>

                    <div class="mt-8 rounded-[1.75rem] border border-white/70 bg-white/55 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Tirage du jour</p>
                                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Tes lettres</h3>
                            </div>
                            <span class="chronomots-pill">{{ count($payload['letters']) }} lettres</span>
                        </div>

                        <div class="mt-5 grid grid-cols-4 gap-3">
                            @foreach ($payload['letters'] as $letter)
                                <div class="chronomots-soft-card chronomots-token chronomots-token--letters flex min-h-18 items-center justify-center rounded-[1.4rem] px-3 py-4">
                                    <span class="text-2xl font-black tracking-[-0.05em] text-slate-950">{{ $letter }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-6 grid gap-4 sm:grid-cols-3">
                        <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 p-5 shadow-sm" data-feedback-reveal>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Cible</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $payload['target_number'] }}</p>
                        </div>
                        <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-cyan-100 via-white to-sky-50 p-5 shadow-sm" data-feedback-reveal>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Calcul soumis</p>
                            <p class="mt-3 text-xl font-black tracking-[-0.05em] text-slate-950">{{ $attempt->submitted_solution }}</p>
                        </div>
                        <div class="chronomots-score-burst chronomots-score-burst--spotlight rounded-[1.75rem] bg-gradient-to-br from-orange-100 via-white to-amber-50 p-5 shadow-sm" data-feedback-reveal>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Écart</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $attempt->result_payload['difference'] ?? 0 }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ $attempt->is_perfect ? 'Badge parfait du jour débloqué.' : 'Le score parfait demande une cible exacte.' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 rounded-[1.75rem] border border-white/70 bg-white/55 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Tirage du jour</p>
                                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Tes nombres</h3>
                            </div>
                            <span class="chronomots-pill">Cible {{ $payload['target_number'] }}</span>
                        </div>

                        <div class="mt-5 grid grid-cols-3 gap-3">
                            @foreach ($payload['numbers'] as $number)
                                <div class="chronomots-soft-card chronomots-token chronomots-token--numbers flex min-h-18 items-center justify-center rounded-[1.4rem] px-3 py-4">
                                    <span class="text-2xl font-black tracking-[-0.05em] text-slate-950">{{ $number }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="chronomots-kicker">Classement du jour</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Top quotidien</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Une tentative par joueur, même défi pour tous, et un classement mis à jour en direct côté serveur.
                </p>

                <div class="mt-6 chronomots-mini-grid">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur score</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $bestScoreOfDay }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Participants</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $attemptsCount }}</p>
                    </div>
                </div>

                @if ($attempt->is_perfect)
                    <div class="mt-6 rounded-[1.6rem] border border-emerald-200/80 bg-emerald-50/85 p-4 chronomots-achievement-burst" data-audio-autoplay="achievement" data-feedback-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Badge spécial</p>
                        <p class="mt-2 text-lg font-black text-slate-950">Badge parfait du jour</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Ta tentative a atteint le score maximal de ce défi quotidien.
                        </p>
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

                <div class="mt-6 space-y-3">
                    @forelse ($leaderboard as $entry)
                        @php
                            $avatar = app(\App\Services\AvatarCatalogService::class)->avatarForUser($entry->user);
                        @endphp
                        <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-950 text-xs font-black text-white">
                                        {{ $loop->iteration }}
                                    </div>
                                    <x-player-avatar :avatar="$avatar" :title="$entry->user->name" :subtitle="null" size="sm" />
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-black text-slate-950">{{ $entry->score }}</p>
                                    @if ($entry->is_perfect)
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Parfait</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] bg-white/80 p-4 shadow-sm">
                            <p class="text-sm leading-6 text-slate-600">Aucune autre tentative enregistrée pour le moment.</p>
                        </div>
                    @endforelse
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <a href="{{ route('daily-challenges.index') }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                        Retour aux défis
                    </a>
                    <a href="{{ route('play') }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                        Revenir aux modes
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
