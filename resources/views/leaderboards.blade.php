<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Classements</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Joueurs et meilleures performances
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Un premier classement simple avec avatars, meilleurs scores et repères par catégorie d’âge.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <span class="chronomots-badge">Top joueurs</span>
                <h2 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950">Classement général</h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Chaque joueur apparaît avec son avatar actif pour rendre les classements plus vivants dès cette V1.
                </p>

                @if ($leaders->isEmpty())
                    <div class="mt-8 rounded-[1.75rem] border border-dashed border-slate-200 bg-white/70 p-6 text-center">
                        <p class="text-lg font-bold text-slate-950">Aucun score enregistré pour le moment</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Les avatars et les places du classement apparaîtront ici dès les premières parties terminées.
                        </p>
                    </div>
                @else
                    <div class="mt-8 space-y-3">
                        @foreach ($leaders as $leader)
                            <article class="chronomots-soft-card rounded-[1.5rem] p-5">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-950 text-sm font-black text-white">
                                            {{ $loop->iteration }}
                                        </div>
                                        <x-player-avatar :avatar="$leader['avatar']" :title="$leader['user']->name" :subtitle="$leader['avatar']['name']" size="md" />
                                    </div>

                                    <div class="grid gap-3 sm:grid-cols-3">
                                        <div class="rounded-[1.1rem] bg-white/90 px-4 py-3 text-center shadow-sm">
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur</p>
                                            <p class="mt-1 text-xl font-black text-slate-950">{{ $leader['best_score'] }}</p>
                                        </div>
                                        <div class="rounded-[1.1rem] bg-white/90 px-4 py-3 text-center shadow-sm">
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Moyenne</p>
                                            <p class="mt-1 text-xl font-black text-slate-950">{{ $leader['average_score'] }}</p>
                                        </div>
                                        <div class="rounded-[1.1rem] bg-white/90 px-4 py-3 text-center shadow-sm">
                                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Parties</p>
                                            <p class="mt-1 text-xl font-black text-slate-950">{{ $leader['games_count'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Par catégorie d’âge</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Repères rapides</h3>

                <div class="mt-6 space-y-3">
                    @foreach ($ageHighlights as $highlight)
                        <div class="rounded-[1.5rem] bg-white/80 p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-950">{{ $highlight['age_group']->name }}</p>
                                <span class="chronomots-pill">{{ $highlight['games_count'] }} partie{{ $highlight['games_count'] > 1 ? 's' : '' }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $highlight['age_group']->description }}</p>
                            <p class="mt-3 text-sm font-semibold text-slate-950">Meilleur score actuel : {{ $highlight['best_score'] }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
