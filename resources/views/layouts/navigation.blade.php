@php
    $isAuthenticated = auth()->check();
    $homeUrl = route('home');
    $playUrl = route('play');
    $leaderboardUrl = route('leaderboards');
    $profileUrl = $isAuthenticated ? route('profile.show') : route('register');
@endphp

<nav x-data="{ open: false }" class="px-4 pt-4 sm:px-6 lg:px-8">
    <div class="chronomots-panel mx-auto max-w-7xl rounded-[1.75rem] px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-18 items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ $homeUrl }}" class="inline-flex shrink-0">
                    <x-application-logo />
                </a>

                <div class="hidden items-center gap-2 xl:flex">
                    <x-nav-link :href="$homeUrl" :active="request()->routeIs('home')">
                        {{ __('Accueil') }}
                    </x-nav-link>
                    <x-nav-link :href="$playUrl" :active="request()->routeIs('play')">
                        {{ __('Jouer') }}
                    </x-nav-link>
                    <x-nav-link :href="$leaderboardUrl" :active="request()->routeIs('leaderboards')">
                        {{ __('Classements') }}
                    </x-nav-link>
                    <x-nav-link :href="$profileUrl" :active="request()->routeIs('profile.show') || request()->routeIs('profile.edit')">
                        {{ __('Profil') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden items-center gap-3 sm:flex">
                @guest
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/75 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-white hover:text-slate-950">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-200/25 transition hover:-translate-y-0.5 hover:bg-slate-900">
                        Register
                    </a>
                @endguest

                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/75 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:bg-white hover:text-slate-950">
                        Dashboard
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-cyan-200/25 transition hover:-translate-y-0.5 hover:bg-slate-900">
                            Logout
                        </button>
                    </form>
                @endauth
            </div>

            <div class="flex shrink-0 items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full bg-white/70 p-2 text-slate-500 shadow-sm transition duration-150 ease-in-out hover:text-slate-900 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="chronomots-panel mx-auto mt-3 max-w-7xl rounded-[1.5rem] px-4 py-4">
            <div class="space-y-2">
                <x-responsive-nav-link :href="$homeUrl" :active="request()->routeIs('home')">
                    {{ __('Accueil') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="$playUrl" :active="request()->routeIs('play')">
                    {{ __('Jouer') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="$leaderboardUrl" :active="request()->routeIs('leaderboards')">
                    {{ __('Classements') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="$profileUrl" :active="request()->routeIs('profile.show') || request()->routeIs('profile.edit')">
                    {{ __('Profil') }}
                </x-responsive-nav-link>
            </div>

            <div class="mt-4 border-t border-slate-200/80 pt-4">
                @auth
                    <div class="px-1 pb-3">
                        <div class="font-medium text-base text-slate-900">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                @endauth

                <div class="flex flex-col gap-2">
                    @guest
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                            Register
                        </a>
                    @endguest

                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white/80 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-cyan-200 hover:text-slate-950">
                            Dashboard
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-full bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-900">
                                Logout
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</nav>
