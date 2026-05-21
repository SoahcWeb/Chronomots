<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Jouer</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Choisir son mode
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Sélectionne une catégorie adaptée à l’âge du joueur. La logique de jeu viendra ensuite, mais l’expérience de choix est déjà prête.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="chronomots-panel relative overflow-hidden rounded-[2rem] p-6 sm:p-8">
                <div class="chronomots-orb chronomots-orb--one"></div>
                <div class="chronomots-orb chronomots-orb--two"></div>

                <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <span class="chronomots-badge">Sélection du joueur</span>
                        <h2 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                            Trois parcours pour apprendre en s’amusant.
                        </h2>
                        <p class="mt-4 text-base leading-8 text-slate-600">
                            Chaque mode combine lettres, chiffres, réflexion et rapidité avec une intensité différente. Cette première version pose un choix clair et moderne, prêt pour la future logique backend.
                        </p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[24rem]">
                        <div class="rounded-[1.5rem] bg-white/75 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Catégories</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">{{ $ageGroups->count() }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/75 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Formats</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">Lettres et chiffres</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/75 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Objectif</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">Progression</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                @php
                    $cardThemes = [
                        ['accent' => 'from-cyan-100 via-white to-sky-50', 'text' => 'text-cyan-700', 'badge' => 'Découverte', 'badgeClass' => 'chronomots-badge--info', 'difficulty' => 'Progressif', 'pace' => 'Calme et guidé'],
                        ['accent' => 'from-emerald-100 via-white to-lime-50', 'text' => 'text-emerald-700', 'badge' => 'Entraînement', 'badgeClass' => 'chronomots-badge--success', 'difficulty' => 'Équilibré', 'pace' => 'Rythme actif'],
                        ['accent' => 'from-orange-100 via-white to-amber-50', 'text' => 'text-orange-700', 'badge' => 'Expert', 'badgeClass' => 'chronomots-badge--warning', 'difficulty' => 'Exigeant', 'pace' => 'Rapide et tactique'],
                    ];
                @endphp

                @forelse ($ageGroups as $ageGroup)
                    @php
                        $theme = $cardThemes[$loop->index % count($cardThemes)];
                        $ageLabel = $ageGroup->max_age
                            ? $ageGroup->min_age.'-'.$ageGroup->max_age.' ans'
                            : $ageGroup->min_age.'+';
                    @endphp

                    <article class="chronomots-panel chronomots-interactive chronomots-mode-card flex h-full flex-col rounded-[2rem] p-6 sm:p-8">
                        <div class="chronomots-mode-hero rounded-[1.5rem] bg-gradient-to-br {{ $theme['accent'] }} p-5 shadow-sm">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <span class="chronomots-badge {{ $theme['badgeClass'] }}">
                                        {{ $theme['badge'] }}
                                    </span>
                                    <h3 class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">
                                        {{ $ageGroup->name }}
                                    </h3>
                                    <p class="mt-3 text-sm font-semibold uppercase tracking-[0.18em] {{ $theme['text'] }}">
                                        {{ $theme['difficulty'] }} • {{ $theme['pace'] }}
                                    </p>
                                </div>

                                <span class="chronomots-pill self-start">
                                    {{ $ageLabel }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-6 flex-1 text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>

                        <div class="mt-6 flex flex-wrap gap-2">
                            <span class="chronomots-pill">Lettres</span>
                            <span class="chronomots-pill">Chiffres</span>
                            <span class="chronomots-pill">Adapté à l’âge</span>
                        </div>

                        <div class="chronomots-mode-meta mt-6">
                            <div class="chronomots-soft-card chronomots-mode-stat rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Temps lettres</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $ageGroup->letters_timer_seconds }} s</p>
                                <p class="mt-1 text-sm text-slate-500">Recherche de mot sous pression douce.</p>
                            </div>
                            <div class="chronomots-soft-card chronomots-mode-stat rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Temps chiffres</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $ageGroup->numbers_timer_seconds }} s</p>
                                <p class="mt-1 text-sm text-slate-500">Calcul mental et logique progressive.</p>
                            </div>
                        </div>

                        <div class="mt-6 chronomots-mini-grid">
                            <div class="chronomots-soft-card rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Ambiance</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $theme['pace'] }}</p>
                            </div>
                            <div class="chronomots-soft-card rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Niveau</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $theme['difficulty'] }}</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <a href="{{ route('play.letters.show', $ageGroup) }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-5 py-3.5 text-center text-sm font-semibold uppercase tracking-[0.18em]">
                                Jouer aux lettres
                            </a>
                            <a href="{{ route('play.numbers.show', $ageGroup) }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-5 py-3.5 text-center text-sm font-semibold uppercase tracking-[0.18em]">
                                Jouer aux chiffres
                            </a>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">VS IA Lettres</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($aiLevels as $aiLevel => $aiLabel)
                                        <a href="{{ route('play.letters.show', ['ageGroup' => $ageGroup, 'opponent_level' => $aiLevel]) }}" class="chronomots-pill">
                                            {{ $aiLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">VS IA Chiffres</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($aiLevels as $aiLevel => $aiLabel)
                                        <a href="{{ route('play.numbers.show', ['ageGroup' => $ageGroup, 'opponent_level' => $aiLevel]) }}" class="chronomots-pill">
                                            {{ $aiLabel }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="chronomots-panel rounded-[2rem] p-6 text-center sm:p-8 xl:col-span-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Aucun mode disponible</p>
                        <h3 class="mt-3 text-2xl font-black tracking-[-0.04em] text-slate-950">Les tranches d’âge n’ont pas encore été chargées.</h3>
                        <p class="mx-auto mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Lance le seeder des groupes d’âge pour afficher les modes disponibles sur cette page.
                        </p>
                    </article>
                @endforelse
            </section>

            @auth
                <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Défis quotidiens</p>
                            <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Même tirage pour tous aujourd’hui</h2>
                        </div>

                        <a href="{{ route('daily-challenges.index') }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Voir les défis
                        </a>
                    </div>

                    <div class="mt-6 grid gap-4 lg:grid-cols-2">
                        @foreach ($dailyChallenges as $dailyChallenge)
                            <article class="chronomots-soft-card rounded-[1.5rem] p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-lg font-black text-slate-950">
                                            Défi {{ $dailyChallenge->game_type === 'letters' ? 'lettres' : 'chiffres' }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $dailyChallenge->ageGroup->name }}</p>
                                    </div>

                                    <span class="chronomots-pill">{{ $dailyChallenge->challenge_date->format('d/m') }}</span>
                                </div>

                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    {{ $dailyChallenge->game_type === 'letters' ? 'Une tentative pour faire le meilleur mot possible.' : 'Une tentative pour atteindre la cible commune du jour.' }}
                                </p>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="chronomots-pill">Tentative unique</span>
                                    <span class="chronomots-pill">Classement quotidien</span>
                                </div>

                                <a href="{{ route('daily-challenges.show', $dailyChallenge) }}" class="chronomots-button-primary mt-5 inline-flex items-center justify-center rounded-full px-5 py-3 text-sm font-semibold uppercase tracking-[0.18em]">
                                    Lancer le défi
                                </a>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endauth
        </div>
    </div>
</x-app-layout>
