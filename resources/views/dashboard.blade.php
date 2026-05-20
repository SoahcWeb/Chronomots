<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Espace joueur</p>
                <h2 class="mt-2 text-2xl font-black leading-tight tracking-[-0.04em] text-slate-950">
                    {{ __('Bienvenue dans Chronomots') }}
                </h2>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Ton profil est prêt. Tu pourras bientôt y retrouver ta progression, tes catégories d’âge et tes meilleurs scores.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                <section class="chronomots-panel overflow-hidden rounded-[2rem] p-6 sm:p-8">
                    <span class="chronomots-badge">Profil joueur actif</span>
                    <h3 class="mt-6 text-3xl font-black tracking-[-0.04em] text-slate-950">Prêt pour les premières parties</h3>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                        Chronomots mêle lettres et chiffres dans des défis pensés pour chaque tranche d’âge. Cette authentification simple servira de base pour enregistrer les profils joueurs et leurs scores.
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-[1.5rem] bg-white/85 p-5 shadow-sm">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-700">7-9 ans</p>
                            <p class="mt-2 text-sm text-slate-600">Découverte, repérage et automatismes.</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/85 p-5 shadow-sm">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-700">10-13 ans</p>
                            <p class="mt-2 text-sm text-slate-600">Logique, orthographe et vitesse.</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-white/85 p-5 shadow-sm">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-700">14+</p>
                            <p class="mt-2 text-sm text-slate-600">Défis chrono et progression mesurable.</p>
                        </div>
                    </div>
                </section>

                <aside class="space-y-5">
                    <div class="chronomots-panel rounded-[2rem] p-6">
                        <h3 class="text-lg font-bold text-slate-950">Actions rapides</h3>
                        <div class="mt-5 space-y-3">
                            <a href="{{ url('/') }}" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold uppercase tracking-[0.22em] text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                                Commencer à jouer
                            </a>
                            <a href="{{ route('profile.edit') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white/80 px-5 py-3 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                                Gérer mon profil
                            </a>
                        </div>
                    </div>

                    <div class="chronomots-panel rounded-[2rem] p-6">
                        <h3 class="text-lg font-bold text-slate-950">Suite logique</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            La prochaine étape naturelle sera d’ajouter les profils joueurs détaillés, l’historique des parties et le tableau des scores.
                        </p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
