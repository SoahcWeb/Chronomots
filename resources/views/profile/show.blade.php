<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Profil</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Vue d’ensemble du joueur
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Une page simple pour regrouper les informations essentielles du profil avant d’ajouter plus de logique.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1fr_0.92fr]">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <span class="chronomots-badge">Page /profile</span>
                <h2 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950">Profil joueur actif</h2>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Cette page sert de point d’entrée simple pour le profil. Elle affichera plus tard les scores, la progression et les préférences du joueur.
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">Nom du joueur</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ auth()->user()->name }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Email</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Actions rapides</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Navigation du joueur</h3>

                <div class="mt-6 space-y-3">
                    <a href="{{ route('dashboard') }}" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold uppercase tracking-[0.2em] text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                        Aller au dashboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white/80 px-5 py-3 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                        Modifier le profil
                    </a>
                    <a href="{{ route('leaderboards') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white/80 px-5 py-3 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                        Voir les classements
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
