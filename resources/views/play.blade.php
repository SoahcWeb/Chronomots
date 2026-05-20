@php
    $modes = [
        [
            'label' => '7-9 ans',
            'description' => 'Une entrée en douceur pour découvrir les lettres, les chiffres et les premiers réflexes chrono dans un cadre rassurant.',
            'difficulty' => 'Découverte',
            'duration' => '6 min',
            'accent' => 'from-cyan-100 via-white to-sky-50',
            'text' => 'text-cyan-700',
            'badge' => 'Premiers défis',
        ],
        [
            'label' => '10-13 ans',
            'description' => 'Des séries plus variées pour entraîner vocabulaire, logique numérique et rapidité avec un rythme équilibré.',
            'difficulty' => 'Intermédiaire',
            'duration' => '9 min',
            'accent' => 'from-emerald-100 via-white to-lime-50',
            'text' => 'text-emerald-700',
            'badge' => 'Progression active',
        ],
        [
            'label' => '14+',
            'description' => 'Un mode plus soutenu pour travailler précision, réflexion et vitesse dans des manches plus exigeantes.',
            'difficulty' => 'Avancé',
            'duration' => '12 min',
            'accent' => 'from-orange-100 via-white to-amber-50',
            'text' => 'text-orange-700',
            'badge' => 'Défi intensif',
        ],
    ];
@endphp

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
                            <p class="mt-2 text-2xl font-black text-slate-950">3</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/75 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Formats</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">Lettres + chiffres</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/75 p-4 shadow-sm backdrop-blur-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Objectif</p>
                            <p class="mt-2 text-2xl font-black text-slate-950">Progression</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-3">
                @foreach ($modes as $mode)
                    <article class="chronomots-panel flex h-full flex-col rounded-[2rem] p-6 sm:p-8">
                        <div class="rounded-[1.5rem] bg-gradient-to-br {{ $mode['accent'] }} p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.22em] {{ $mode['text'] }}">
                                        {{ $mode['badge'] }}
                                    </p>
                                    <h3 class="mt-3 text-3xl font-black tracking-[-0.04em] text-slate-950">
                                        {{ $mode['label'] }}
                                    </h3>
                                </div>

                                <span class="rounded-full border border-white/80 bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                                    {{ $mode['difficulty'] }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-6 flex-1 text-sm leading-7 text-slate-600">
                            {{ $mode['description'] }}
                        </p>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <div class="chronomots-soft-card rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Difficulté</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $mode['difficulty'] }}</p>
                            </div>
                            <div class="chronomots-soft-card rounded-[1.4rem] p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Temps moyen</p>
                                <p class="mt-2 text-lg font-bold text-slate-950">{{ $mode['duration'] }}</p>
                            </div>
                        </div>

                        <button type="button" class="mt-6 inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-3.5 text-sm font-semibold uppercase tracking-[0.18em] text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                            Jouer
                        </button>
                    </article>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
