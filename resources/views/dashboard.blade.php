<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Dashboard joueur</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Bonjour {{ auth()->user()->name }}, voici ton espace Chronomots
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Retrouve tes scores, ta progression par âge et tes dernières parties dans un seul tableau de bord.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="chronomots-panel relative overflow-hidden rounded-[2rem] p-6 sm:p-8">
                <div class="chronomots-orb chronomots-orb--one"></div>
                <div class="chronomots-orb chronomots-orb--two"></div>

                <div class="relative z-10 grid gap-6 lg:grid-cols-[1.1fr_0.9fr] lg:items-end">
                    <div>
                        <span class="chronomots-badge">Vue d’ensemble</span>
                        <h2 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                            {{ $hasGames ? 'Ta progression prend forme.' : 'Prêt pour ta première partie ?' }}
                        </h2>
                        <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600">
                            @if ($hasGames)
                                Tu as déjà construit une vraie base de progression entre lettres et chiffres. Utilise ce dashboard pour suivre tes performances et relancer rapidement une partie.
                            @else
                                Tu n’as pas encore de partie enregistrée. Lance un premier défi lettres ou chiffres pour commencer à remplir ton historique et débloquer tes statistiques personnelles.
                            @endif
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a
                                href="{{ $preferredAgeGroup ? route('play.letters.show', $preferredAgeGroup) : route('play') }}"
                                class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Jouer aux lettres
                            </a>
                            <a
                                href="{{ $preferredAgeGroup ? route('play.numbers.show', $preferredAgeGroup) : route('play') }}"
                                class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Jouer aux chiffres
                            </a>
                            <a
                                href="{{ route('play') }}"
                                class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Choisir un mode
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.6rem] bg-white/78 p-5 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Parties jouées</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $totalGames }}</p>
                        </div>
                        <div class="rounded-[1.6rem] bg-white/78 p-5 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Score moyen</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $averageScore }}</p>
                        </div>
                        <div class="rounded-[1.6rem] bg-white/78 p-5 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur Lettres</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $bestLettersScore }}</p>
                        </div>
                        <div class="rounded-[1.6rem] bg-white/78 p-5 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur Chiffres</p>
                            <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $bestNumbersScore }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-6">
                    <div class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Statistiques personnelles</p>
                                <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">
                                    Tes repères de progression
                                </h2>
                            </div>

                            <p class="max-w-md text-sm leading-6 text-slate-600">
                                Une synthèse simple pour comprendre comment tu progresses entre les deux modes.
                            </p>
                        </div>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mode favori</p>
                                <p class="mt-3 text-xl font-black text-slate-950">{{ $favoriteMode }}</p>
                            </div>
                            <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Parties lettres</p>
                                <p class="mt-3 text-xl font-black text-slate-950">{{ $lettersGamesCount }}</p>
                            </div>
                            <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Parties chiffres</p>
                                <p class="mt-3 text-xl font-black text-slate-950">{{ $numbersGamesCount }}</p>
                            </div>
                            <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Catégories actives</p>
                                <p class="mt-3 text-xl font-black text-slate-950">{{ $activeCategories }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Dernières parties jouées</p>
                                <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Historique récent</h2>
                            </div>

                            <p class="max-w-md text-sm leading-6 text-slate-600">
                                Les dernières sessions terminées apparaissent ici avec leur catégorie et leur score.
                            </p>
                        </div>

                        @if ($recentSessions->isEmpty())
                            <div class="mt-6 rounded-[1.75rem] border border-dashed border-slate-200 bg-white/70 p-6 text-center">
                                <p class="text-lg font-bold text-slate-950">Aucune partie enregistrée pour le moment</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Lance une première partie pour voir apparaître ton historique récent.
                                </p>
                            </div>
                        @else
                            <div class="mt-6 space-y-3">
                                @foreach ($recentSessions as $session)
                                    <article class="chronomots-soft-card rounded-[1.5rem] p-4 sm:p-5">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="chronomots-pill">{{ ucfirst($session->game_type) }}</span>
                                                    <span class="chronomots-pill">{{ $session->ageGroup?->name ?? 'Catégorie inconnue' }}</span>
                                                </div>
                                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                                    Partie terminée le {{ optional($session->completed_at ?? $session->updated_at)->format('d/m/Y à H:i') }}
                                                </p>
                                            </div>

                                            <div class="rounded-[1.3rem] bg-white/90 px-4 py-3 text-center shadow-sm">
                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Score</p>
                                                <p class="mt-1 text-2xl font-black tracking-[-0.05em] text-slate-950">{{ $session->score }}</p>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Progression par catégorie d’âge</p>
                        <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Répartition de tes progrès</h2>

                        <div class="mt-6 space-y-4">
                            @foreach ($progression as $item)
                                @php
                                    $ageGroup = $item['age_group'];
                                @endphp

                                <article class="chronomots-soft-card rounded-[1.6rem] p-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-lg font-black text-slate-950">{{ $ageGroup->name }}</p>
                                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $ageGroup->description }}</p>
                                        </div>

                                        <div class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600 shadow-sm">
                                            {{ $item['games_count'] }} partie{{ $item['games_count'] > 1 ? 's' : '' }}
                                        </div>
                                    </div>

                                    <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full bg-gradient-to-r from-cyan-400 via-emerald-400 to-lime-400"
                                            style="width: {{ $item['completion_percent'] }}%;"
                                        ></div>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur score</p>
                                            <p class="mt-1 text-lg font-bold text-slate-950">{{ $item['best_score'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Score moyen</p>
                                            <p class="mt-1 text-lg font-bold text-slate-950">{{ $item['average_score'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">L / C</p>
                                            <p class="mt-1 text-lg font-bold text-slate-950">{{ $item['letters_games'] }} / {{ $item['numbers_games'] }}</p>
                                        </div>
                                    </div>

                                    @if (! $item['has_progress'])
                                        <p class="mt-4 text-sm leading-6 text-slate-500">
                                            Aucune partie encore jouée dans cette catégorie.
                                        </p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    </div>

                    <div class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Actions rapides</p>
                        <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Relancer une session</h2>

                        <div class="mt-6 flex flex-col gap-3">
                            <a
                                href="{{ $preferredAgeGroup ? route('play.letters.show', $preferredAgeGroup) : route('play') }}"
                                class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Jouer aux lettres
                            </a>
                            <a
                                href="{{ $preferredAgeGroup ? route('play.numbers.show', $preferredAgeGroup) : route('play') }}"
                                class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Jouer aux chiffres
                            </a>
                            <a
                                href="{{ route('play') }}"
                                class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Choisir un mode
                            </a>
                            <a
                                href="{{ route('profile.show') }}"
                                class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]"
                            >
                                Voir mon profil
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
