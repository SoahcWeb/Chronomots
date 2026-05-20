<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Chronomots, le jeu éducatif de lettres et de chiffres adapté à chaque tranche d'âge.">

        <title>{{ config('app.name', 'Chronomots') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="chronomots-grid font-sans antialiased">
        <div class="relative overflow-hidden">
            <div class="chronomots-orb chronomots-orb--one"></div>
            <div class="chronomots-orb chronomots-orb--two"></div>

            <header class="mx-auto flex max-w-7xl items-center justify-between px-4 py-6 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="inline-flex">
                    <x-application-logo />
                </a>

                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hidden rounded-full border border-slate-200 bg-white/75 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:text-slate-950 sm:inline-flex">
                            Mon espace
                        </a>
                        <a href="{{ route('profile.edit') }}" class="inline-flex rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                            Mon profil
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden rounded-full border border-slate-200 bg-white/75 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:text-slate-950 sm:inline-flex">
                            Connexion
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                            Créer un profil
                        </a>
                    @endauth
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 pb-16 pt-4 sm:px-6 lg:px-8 lg:pb-24 lg:pt-8">
                <section class="grid gap-8 lg:grid-cols-[1.08fr_0.92fr] lg:items-center">
                    <div class="max-w-3xl">
                        <span class="chronomots-badge">Jeu éducatif par Nethra Gaming</span>
                        <h1 class="mt-6 text-5xl font-black leading-[0.95] tracking-[-0.06em] text-slate-950 sm:text-6xl lg:text-7xl">
                            Chronomots fait jouer les lettres et les chiffres au rythme de chaque âge.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                            Chronomots est un jeu éducatif moderne qui combine vocabulaire, logique et rapidité dans des sessions adaptées aux enfants, adolescents et plus grands joueurs.
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="@auth{{ route('dashboard') }}@else{{ route('register') }}@endauth" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.2em] text-white transition hover:-translate-y-0.5 hover:bg-slate-900">
                                Commencer à jouer
                            </a>
                            @guest
                                <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 px-6 py-3.5 text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                                    J’ai déjà un profil
                                </a>
                            @endguest
                        </div>

                        <div class="mt-10 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-[1.6rem] bg-white/80 p-5 shadow-sm shadow-slate-100">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-700">Lettres</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Lecture, orthographe et agilité mentale dans des mini-défis motivants.</p>
                            </div>
                            <div class="rounded-[1.6rem] bg-white/80 p-5 shadow-sm shadow-slate-100">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-emerald-700">Chiffres</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Calcul, logique et suites numériques pour progresser sans pression.</p>
                            </div>
                            <div class="rounded-[1.6rem] bg-white/80 p-5 shadow-sm shadow-slate-100">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-orange-700">Progression</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Une base d’auth simple pour préparer les profils joueurs et les scores.</p>
                            </div>
                        </div>
                    </div>

                    <div class="chronomots-panel rounded-[2rem] p-6 sm:p-8">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Catégories de jeu</p>
                                <h2 class="mt-2 text-3xl font-black tracking-[-0.04em] text-slate-950">Des défis pensés pour chaque étape.</h2>
                            </div>
                            <div class="rounded-full bg-slate-950/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600">
                                Responsive
                            </div>
                        </div>

                        <div class="mt-8 space-y-4">
                            <article class="rounded-[1.75rem] bg-gradient-to-r from-cyan-50 to-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-slate-950">7-9 ans</h3>
                                    <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-cyan-800">Découverte</span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Premiers repères sur les lettres, reconnaissance visuelle, petits calculs et parties courtes pour construire la confiance.
                                </p>
                            </article>

                            <article class="rounded-[1.75rem] bg-gradient-to-r from-emerald-50 to-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-slate-950">10-13 ans</h3>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-800">Progression</span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Orthographe, logique, rapidité et lecture active dans des sessions plus denses, adaptées à la montée en autonomie.
                                </p>
                            </article>

                            <article class="rounded-[1.75rem] bg-gradient-to-r from-orange-50 to-white p-5 shadow-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <h3 class="text-xl font-bold text-slate-950">14+</h3>
                                    <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-orange-800">Challenge</span>
                                </div>
                                <p class="mt-3 text-sm leading-6 text-slate-600">
                                    Défis chrono, précision sur les nombres et maîtrise du vocabulaire pour les joueurs qui veulent un vrai niveau de challenge.
                                </p>
                            </article>
                        </div>

                        <div class="mt-8 rounded-[1.75rem] bg-slate-950 p-5 text-white shadow-xl shadow-slate-900/15">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan-200">Concept</p>
                            <p class="mt-3 text-sm leading-7 text-slate-200">
                                Un jeu éducatif de lettres et de chiffres, adapté par âge, conçu pour être clair, dynamique et prêt à accueillir les profils joueurs ainsi que leurs scores.
                            </p>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
