<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Classements</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Vue d’ensemble des futurs scores
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Une base simple pour accueillir les scores, les meilleures performances et les tableaux de progression.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[0.95fr_1.05fr]">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <span class="chronomots-badge">Page /leaderboards</span>
                <h2 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950">Classements bientôt disponibles</h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Cette page est prête pour accueillir plus tard les meilleurs scores par catégorie d’âge, les performances récentes et les progrès des joueurs.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">7-9 ans</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Top des premières réussites et progression douce.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">10-13 ans</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Performances équilibrées entre logique et rapidité.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-orange-700">14+</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Résultats plus compétitifs et meilleurs temps à venir.</p>
                    </div>
                </div>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Structure de base</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Ce que cette page accueillera ensuite</h3>

                <div class="mt-6 space-y-3">
                    <div class="rounded-[1.5rem] bg-white/80 p-4 shadow-sm">
                        <p class="font-semibold text-slate-950">Meilleurs scores</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Classement général et par catégorie d’âge.</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-white/80 p-4 shadow-sm">
                        <p class="font-semibold text-slate-950">Dernières performances</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Historique simple des parties les plus marquantes.</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-white/80 p-4 shadow-sm">
                        <p class="font-semibold text-slate-950">Progression</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Comparaison des résultats et repères de progression.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
