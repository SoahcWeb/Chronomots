<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Accueil</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Bienvenue dans Chronomots
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Une base claire et cohérente pour le jeu éducatif de lettres et de chiffres signé Nethra Gaming.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.12fr_0.88fr] lg:items-start">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <span class="chronomots-badge">Chronomots</span>
                <h2 class="mt-6 text-4xl font-black leading-tight tracking-[-0.05em] text-slate-950 sm:text-5xl">
                    Le jeu éducatif qui fait progresser en lettres et en chiffres.
                </h2>
                <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600">
                    Chronomots propose une expérience moderne, accessible et adaptée par âge. Cette page d’accueil pose le cadre du projet sans encore lancer la logique complète du jeu.
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('play') }}" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.2em] text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                        Commencer à jouer
                    </a>
                    <a href="{{ route('leaderboards') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                        Voir les classements
                    </a>
                </div>

                <div class="mt-10 rounded-[2rem] border border-white/70 bg-white/60 p-5 shadow-sm shadow-slate-100 backdrop-blur-sm sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Compétences explorées</p>
                            <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Une structure prête pour grandir.</h3>
                        </div>
                        <p class="max-w-md text-sm leading-6 text-slate-600">
                            Chaque page garde une présentation simple et unifiée pour faciliter la suite du développement.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">Lettres</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Lecture, vocabulaire et orthographe avec une progression visible.</p>
                        </div>
                        <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Chiffres</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Calcul, suites et logique dans des formats rapides.</p>
                        </div>
                        <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-violet-700">Réflexion</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Observation, stratégie et résolution simple de défis.</p>
                        </div>
                        <div class="chronomots-soft-card rounded-[1.5rem] p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-orange-700">Rapidité</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Rythme, concentration et petites séquences chrono.</p>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Catégories</p>
                <h3 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950">Des parcours pensés pour chaque âge.</h3>

                <div class="mt-8 space-y-4">
                    <article class="rounded-[1.75rem] bg-gradient-to-r from-cyan-50 to-white p-5 shadow-sm">
                        <h4 class="text-xl font-bold text-slate-950">7-9 ans</h4>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Découverte des lettres, repérage visuel et premiers calculs.</p>
                    </article>

                    <article class="rounded-[1.75rem] bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
                        <h4 class="text-xl font-bold text-slate-950">10-13 ans</h4>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Progression sur l’orthographe, la logique et la vitesse.</p>
                    </article>

                    <article class="rounded-[1.75rem] bg-gradient-to-r from-orange-50 to-white p-5 shadow-sm">
                        <h4 class="text-xl font-bold text-slate-950">14+</h4>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Défis plus soutenus pour la précision, la réflexion et le chrono.</p>
                    </article>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
