<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6">
        <span class="chronomots-badge">Connexion joueur</span>
        <h1 class="mt-4 text-3xl font-black tracking-[-0.04em] text-slate-950">Retrouve ton profil Chronomots</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">
            Connecte-toi pour accéder à ton espace joueur, préparer tes prochaines parties et suivre bientôt tes scores.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="text-slate-700" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" class="text-slate-700" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-cyan-600 shadow-sm focus:ring-cyan-400" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Se souvenir de moi') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm font-medium text-slate-600 underline underline-offset-4 transition hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2" href="{{ route('password.request') }}">
                    {{ __('Mot de passe oublié ?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Entrer dans Chronomots') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
