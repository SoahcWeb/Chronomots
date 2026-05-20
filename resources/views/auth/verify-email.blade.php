<x-guest-layout>
    <div class="mb-6">
        <span class="chronomots-badge">Vérification email</span>
        <p class="mt-4 text-sm leading-6 text-slate-600">
            {{ __('Merci pour ton inscription. Avant de commencer, vérifie ton adresse email via le lien qui vient de t’être envoyé. Si tu ne l’as pas reçu, nous pouvons en renvoyer un.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-emerald-600">
            {{ __('Un nouveau lien de vérification a été envoyé à l’adresse email utilisée lors de l’inscription.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Renvoyer l’email de vérification') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm font-medium text-slate-600 underline underline-offset-4 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-cyan-400 focus:ring-offset-2">
                {{ __('Déconnexion') }}
            </button>
        </form>
    </div>
</x-guest-layout>
