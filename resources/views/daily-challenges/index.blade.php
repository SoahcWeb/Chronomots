<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Défis quotidiens</p>
                <h1 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950 sm:text-4xl">
                    Le même défi pour tout le monde, chaque jour
                </h1>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Deux rendez-vous quotidiens, un en lettres et un en chiffres, avec une tentative unique et un classement commun.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <section class="grid gap-6 lg:grid-cols-2">
                @foreach ($todayChallenges as $entry)
                    @php
                        $challenge = $entry['challenge'];
                        $attempt = $entry['user_attempt'];
                        $payload = $challenge->payload;
                        $isLetters = $challenge->game_type === 'letters';
                    @endphp

                    <article class="chronomots-panel rounded-[2rem] p-6 sm:p-8" data-feedback-reveal data-feedback-delay="{{ 40 + ($loop->index * 80) }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <span class="chronomots-badge {{ $isLetters ? 'chronomots-badge--info' : 'chronomots-badge--success' }}">
                                    Défi {{ $isLetters ? 'lettres' : 'chiffres' }}
                                </span>
                                <h2 class="mt-5 text-3xl font-black tracking-[-0.04em] text-slate-950">
                                    {{ $challenge->ageGroup->name }}
                                </h2>
                                <p class="mt-3 text-sm leading-7 text-slate-600">
                                    {{ $challenge->ageGroup->description }}
                                </p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[1.3rem] bg-white/82 px-4 py-3 text-center shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Meilleur score</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950">{{ $entry['best_score'] }}</p>
                                </div>
                                <div class="rounded-[1.3rem] bg-white/82 px-4 py-3 text-center shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tentatives</p>
                                    <p class="mt-1 text-2xl font-black text-slate-950">{{ $entry['attempts_count'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 {{ $isLetters ? 'sm:grid-cols-2' : 'sm:grid-cols-[1fr_auto]' }}">
                            @if ($isLetters)
                                <div class="rounded-[1.5rem] bg-white/70 p-5 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Tirage du jour</p>
                                    <div class="mt-4 grid grid-cols-4 gap-2">
                                        @foreach ($payload['letters'] as $letter)
                                            <div class="chronomots-soft-card flex min-h-14 items-center justify-center rounded-[1rem] px-2 py-3">
                                                <span class="text-xl font-black text-slate-950">{{ $letter }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="rounded-[1.5rem] bg-white/70 p-5 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Nombres du jour</p>
                                    <div class="mt-4 grid grid-cols-3 gap-2">
                                        @foreach ($payload['numbers'] as $number)
                                            <div class="chronomots-soft-card flex min-h-14 items-center justify-center rounded-[1rem] px-2 py-3">
                                                <span class="text-xl font-black text-slate-950">{{ $number }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-[1.5rem] bg-gradient-to-br from-emerald-100 via-white to-lime-50 px-6 py-5 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Cible</p>
                                    <p class="mt-2 text-4xl font-black tracking-[-0.05em] text-slate-950">{{ $payload['target_number'] }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                @if ($attempt)
                                    <p class="text-sm font-semibold text-emerald-700">Déjà tenté aujourd’hui</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Ton score: {{ $attempt->score }}
                                        @if ($attempt->is_perfect)
                                            • Badge parfait du jour
                                        @endif
                                    </p>
                                @else
                                    <p class="text-sm font-semibold text-slate-950">Une seule tentative aujourd’hui</p>
                                    <p class="mt-1 text-sm leading-6 text-slate-600">
                                        Le score est calculé côté serveur, à partir du même défi pour tous les joueurs.
                                    </p>
                                @endif
                            </div>

                            <a href="{{ route('daily-challenges.show', $challenge) }}" class="chronomots-button-primary inline-flex items-center justify-center rounded-full px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em]">
                                {{ $attempt ? 'Voir le résultat' : 'Lancer le défi' }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Historique des défis</p>
                        <h2 class="mt-2 text-2xl font-black tracking-[-0.04em] text-slate-950">Jours précédents</h2>
                    </div>

                    <p class="max-w-md text-sm leading-6 text-slate-600">
                        Retrouve tes anciens défis avec le meilleur score obtenu dans la journée et ton résultat personnel.
                    </p>
                </div>

                @if ($history->isEmpty())
                    <div class="mt-6 rounded-[1.75rem] border border-dashed border-slate-200 bg-white/70 p-6 text-center">
                        <p class="text-lg font-bold text-slate-950">Pas encore d’historique</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Les anciens défis apparaîtront ici dès demain.
                        </p>
                    </div>
                @else
                    <div class="mt-6 grid gap-3 lg:grid-cols-2">
                        @foreach ($history as $entry)
                            @php
                                $challenge = $entry['challenge'];
                                $attempt = $entry['user_attempt'];
                            @endphp

                            <article class="chronomots-soft-card rounded-[1.5rem] p-5" data-feedback-reveal data-feedback-delay="{{ 80 + ($loop->index * 60) }}">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-lg font-black text-slate-950">
                                            {{ $challenge->game_type === 'letters' ? 'Défi lettres' : 'Défi chiffres' }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-600">
                                            {{ $challenge->challenge_date->format('d/m/Y') }} • {{ $challenge->ageGroup->name }}
                                        </p>
                                    </div>

                                    <span class="chronomots-pill">Top du jour: {{ $entry['best_score'] }}</span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="chronomots-pill">{{ $entry['attempts_count'] }} tentative{{ $entry['attempts_count'] > 1 ? 's' : '' }}</span>
                                    @if ($attempt)
                                        <span class="chronomots-pill">Ton score: {{ $attempt->score }}</span>
                                        @if ($attempt->is_perfect)
                                            <span class="chronomots-badge chronomots-badge--success">Badge parfait du jour</span>
                                        @endif
                                    @else
                                        <span class="chronomots-pill">Non tenté</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
