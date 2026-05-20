<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Résultat Lettres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Partie terminée pour {{ $ageGroup->name }}
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Voici le résultat de cette première manche solo. Tu peux rejouer immédiatement avec un nouveau tirage.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.05fr_0.95fr]">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <span class="chronomots-badge">Score obtenu</span>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[1.75rem] bg-gradient-to-br from-cyan-100 via-white to-sky-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Mot soumis</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $submittedWord }}</p>
                    </div>
                    <div class="rounded-[1.75rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 p-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Score</p>
                        <p class="mt-3 text-3xl font-black tracking-[-0.05em] text-slate-950">{{ $score }} pts</p>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Tirage utilisé</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-5">
                        @foreach ($letters as $letter)
                            <div class="chronomots-soft-card flex min-h-18 items-center justify-center rounded-[1.4rem] bg-white/90 px-3 py-4">
                                <span class="text-2xl font-black tracking-[-0.05em] text-slate-950">{{ $letter }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Résumé de session</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Partie enregistrée</h3>

                <div class="mt-6 space-y-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Type</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">Lettres</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Statut</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $gameSession->status }}</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Mot enregistré</p>
                        <p class="mt-2 text-lg font-bold text-slate-950">{{ $letterRound->submitted_word }}</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <a href="{{ route('play.letters.show', $ageGroup) }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                        Rejouer
                    </a>
                    <a href="{{ route('play') }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                        Retour aux modes
                    </a>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
