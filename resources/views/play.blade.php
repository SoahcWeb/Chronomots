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
                        ['accent' => 'from-cyan-100 via-white to-sky-50', 'text' => 'text-cyan-700', 'badge' => 'Découverte'],
                        ['accent' => 'from-emerald-100 via-white to-lime-50', 'text' => 'text-emerald-700', 'badge' => 'Entraînement'],
                        ['accent' => 'from-orange-100 via-white to-amber-50', 'text' => 'text-orange-700', 'badge' => 'Expert'],
                    ];
                @endphp

                @forelse ($ageGroups as $ageGroup)
                    @php
                        $theme = $cardThemes[$loop->index % count($cardThemes)];
                        $ageLabel = $ageGroup->max_age
                            ? $ageGroup->min_age.'-'.$ageGroup->max_age.' ans'
                            : $ageGroup->min_age.'+';
                    @endphp

                    <article class="chronomots-panel chronomots-interactive flex h-full flex-col rounded-[2rem] p-6 sm:p-8">
                        <div class="rounded-[1.5rem] bg-gradient-to-br {{ $theme['accent'] }} p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $theme['text'] }}">
                                        {{ $theme['badge'] }}
                                    </p>
                                    <h3 class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">
                                        {{ $ageGroup->name }}
                                    </h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                                    {{ $ageLabel }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-6 flex-1 text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="chronomots-soft-card rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Temps lettres</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $ageGroup->letters_timer_seconds }} s</p>
                            </div>
                            <div class="chronomots-soft-card rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Temps chiffres</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $ageGroup->numbers_timer_seconds }} s</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <a href="{{ route('play.letters.show', $ageGroup) }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-5 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                                Jouer aux lettres
                            </a>
                            <button type="button" disabled class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-5 py-3.5 text-sm font-semibold uppercase tracking-[0.18em] opacity-60">
                                Jouer aux chiffres
                            </button>
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
        </div>
    </div>
</x-app-layout>
