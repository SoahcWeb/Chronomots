<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Chronomots') }} | Nethra Gaming</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="chronomots-grid font-sans text-slate-900 antialiased">
        <div class="relative min-h-screen px-4 py-8 sm:px-6 lg:px-8">
            <div class="chronomots-orb chronomots-orb--one"></div>
            <div class="chronomots-orb chronomots-orb--two"></div>

            <div class="mx-auto grid min-h-[calc(100vh-8rem)] w-full max-w-6xl items-center gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                <aside class="chronomots-panel hidden rounded-[2rem] p-8 text-slate-800 lg:block">
                    <a href="{{ url('/') }}" class="inline-flex">
                        <x-application-logo />
                    </a>

                    <div class="mt-8 space-y-5">
                        <span class="chronomots-badge">Studio Nethra Gaming</span>
                        <h1 class="max-w-md text-4xl font-black leading-tight tracking-[-0.04em] text-slate-950">
                            Des parties rapides pour progresser en lettres et en chiffres.
                        </h1>
                        <p class="max-w-lg text-base leading-7 text-slate-600">
                            Chronomots adapte les défis par âge pour aider chaque joueur à progresser à son rythme, tout en gardant une expérience ludique et motivante.
                        </p>
                    </div>

                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-3xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">7-9 ans</p>
                            <p class="mt-2 text-sm text-slate-600">Premiers réflexes en lecture, repérage de lettres et calcul rapide.</p>
                        </div>
                        <div class="rounded-3xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">10-13 ans</p>
                            <p class="mt-2 text-sm text-slate-600">Défis d’orthographe, logique et rythme pour gagner en confiance.</p>
                        </div>
                        <div class="rounded-3xl bg-white/80 p-4 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-orange-700">14+</p>
                            <p class="mt-2 text-sm text-slate-600">Challenges plus denses, vitesse, précision et progression personnelle.</p>
                        </div>
                    </div>
                </aside>

                <div class="chronomots-panel w-full max-w-xl justify-self-center rounded-[2rem] p-6 sm:p-8">
                    <div class="mb-6 flex flex-col items-center text-center lg:hidden">
                        <a href="{{ url('/') }}" class="inline-flex">
                            <x-application-logo />
                        </a>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-600">
                            Connecte-toi pour gérer ton profil joueur et retrouver bientôt tes scores.
                        </p>
                    </div>

                    {{ $slot }}
                </div>
            </div>

            <div class="mx-auto mt-6 max-w-6xl">
                @include('layouts.footer')
            </div>
        </div>
    </body>
</html>
