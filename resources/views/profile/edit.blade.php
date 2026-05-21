<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-cyan-700">Compte joueur</p>
                <h2 class="text-2xl font-black leading-tight tracking-[-0.04em] text-slate-950">
                    {{ __('Mon profil') }}
                </h2>
            </div>

            <p class="max-w-2xl text-sm leading-6 text-slate-600">
                Mets à jour tes informations, sécurise ton accès et garde ton espace joueur prêt pour la suite de Chronomots.
            </p>
        </div>
    </x-slot>

    <div class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            <div class="chronomots-panel p-4 shadow-none sm:rounded-[2rem] sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="chronomots-panel p-4 shadow-none sm:rounded-[2rem] sm:p-8">
                <div class="max-w-5xl">
                    @include('profile.partials.update-avatar-form')
                </div>
            </div>

            <div class="chronomots-panel p-4 shadow-none sm:rounded-[2rem] sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="chronomots-panel p-4 shadow-none sm:rounded-[2rem] sm:p-8">
                <div class="max-w-2xl">
                    @include('profile.partials.update-audio-preferences-form')
                </div>
            </div>

            <div class="chronomots-panel p-4 shadow-none sm:rounded-[2rem] sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
