<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Mode Lettres</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    {{ $ageGroup->name }} : trouve le meilleur mot possible
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Utilise uniquement les lettres affichées. Pour cette première version, le score dépend simplement de la longueur du mot proposé.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <span class="chronomots-badge">Tirage en cours</span>
                        <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                            Compose un mot avec {{ $lettersCount }} lettres disponibles
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
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Chrono lettres</p>
                        <p class="mt-3 text-4xl font-black tracking-[-0.05em] text-slate-950">
                            <span x-text="minutes()"></span>:<span x-text="seconds()"></span>
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Temps prévu pour {{ $ageGroup->name }}.
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 {{ $lettersCount >= 10 ? 'sm:grid-cols-5' : ($lettersCount >= 8 ? 'sm:grid-cols-4' : 'sm:grid-cols-4') }}">
                    @foreach ($letters as $letter)
                        <div class="chronomots-soft-card flex min-h-20 items-center justify-center rounded-[1.6rem] bg-white/90 px-4 py-5 text-center shadow-sm">
                            <span class="text-3xl font-black tracking-[-0.05em] text-slate-950 sm:text-4xl">{{ $letter }}</span>
                        </div>
                    @endforeach
                </div>

                <form method="POST" action="{{ route('play.letters.submit', $ageGroup) }}" class="mt-8 space-y-4">
                    @csrf
                    <input type="hidden" name="draw_id" value="{{ $drawId }}">

                    <div>
                        <label for="submitted_word" class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">
                            Proposer un mot
                        </label>
                        <input
                            id="submitted_word"
                            name="submitted_word"
                            type="text"
                            maxlength="32"
                            value="{{ old('submitted_word', $submittedWord) }}"
                            class="mt-3 block w-full rounded-[1.4rem] border border-slate-200 bg-white/90 px-5 py-4 text-lg font-semibold uppercase tracking-[0.08em] text-slate-950 shadow-sm outline-none"
                            placeholder="Entre ton mot"
                            autocomplete="off"
                        >

                        @if (! empty($errorMessage))
                            <p class="mt-3 text-sm font-medium text-rose-600">{{ $errorMessage }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <button type="submit" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Valider mon mot
                        </button>
                        <a href="{{ route('play.letters.show', $ageGroup) }}" class="chronomots-button-secondary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                            Nouveau tirage
                        </a>
                    </div>
                </form>
            </section>

            <aside class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Règles de cette V1</p>
                <h3 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Simple, rapide et jouable</h3>

                <div class="mt-6 space-y-3">
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Tirage équilibré</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le mélange contient voyelles et consonnes, avec un nombre total adapté à l’âge.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Validation serveur</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le mot est accepté seulement s’il utilise uniquement les lettres disponibles.</p>
                    </div>
                    <div class="chronomots-soft-card rounded-[1.5rem] p-4">
                        <p class="font-semibold text-slate-950">Score immédiat</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Le score correspond à la longueur du mot multipliée par 10.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
