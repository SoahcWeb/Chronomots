<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-700">Mode Chiffres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $ageGroup->name }} : approche la cible au plus juste
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Utilise uniquement les nombres affichés pour écrire un calcul valide. Cette V1 accepte les opérations `+`, `-`, `*`, `/` et les parenthèses.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <span class="chronomots-badge">Tirage en cours</span>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Atteins la cible {{ $targetNumber }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            {{ $ageGroup->description }}
                        </p>
                    </div>

                    <div
                        x-data="{
                            remaining: {{ $timerSeconds }},
                            minutes() { return String(Math.floor(this.remaining / 60)).padStart(2, '0') },
                            seconds() { return String(this.remaining % 60).padStart(2, '0') },
                            init() {
                                setInterval(() => {
                                    if (this.remaining > 0) {
                                        this.remaining--;
                                    }
                                }, 1000);
                            }
                        }"
                        class="chronomots-soft-card rounded-[1.5rem] p-5 sm:w-48"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Chrono chiffres</p>
                        <p class="mt-3 text-4xl font-black tracking-[-0.05em] text-slate-950">
                            <span x-text="minutes()"></span>:<span x-text="seconds()"></span>
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Temps prévu pour {{ $ageGroup->name }}.
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div class="grid gap-3 {{ count($numbers) >= 6 ? 'sm:grid-cols-3' : 'sm:grid-cols-2' }}">
                        @foreach ($numbers as $number)
                            <div class="chronomots-soft-card flex min-h-20 items-center justify-center rounded-[1.6rem] bg-white/90 px-4 py-5 text-center shadow-sm">
                                <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $number }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="rounded-[1.8rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 px-6 py-5 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Cible</p>
                        <p class="mt-2 text-4xl font-black tracking-[-0.05em] text-slate-950">{{ $targetNumber }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('play.numbers.submit', $ageGroup) }}" class="mt-8 space-y-4">
                    @csrf
                    <input type="hidden" name="draw_id" value="{{ $drawId }}">

                    <div>
                        <label for="submitted_solution" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Écrire un calcul
                        </label>
                        <input
                            id="submitted_solution"
                            name="submitted_solution"
                            type="text"
                            maxlength="255"
                            value="{{ old('submitted_solution', $submittedSolution) }}"
                            class="mt-3 block w-full rounded-[1.4rem] border border-slate-200 bg-white/90 px-5 py-4 text-lg font-semibold tracking-[0.02em] text-slate-950 shadow-sm outline-none"
                            placeholder="Exemple : (25 + 10) * 4"
                            autocomplete="off"
                        >

                        @if (! empty($errorMessage))
                            <p class="mt-3 text-sm font-medium text-rose-600">{{ $errorMessage }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Valider mon calcul
                        </button>
                        <a href="{{ route('play.numbers.show', $ageGroup) }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Nouveau tirage
                        </a>
                    </div>
                </form>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Barème V1</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Simple et lisible</h3>

                <div class="mt-6 space-y-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Exact</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Si ton résultat atteint exactement la cible : 100 points.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Très proche</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Si l’écart est inférieur ou égal à 5 : 50 points.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Proche</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Si l’écart est inférieur ou égal à 10 : 25 points, sinon 0.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
