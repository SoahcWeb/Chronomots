<section>
    <header>
        <h2 class="text-lg font-bold text-slate-950">
            Avatar du joueur
        </h2>

        <p class="mt-1 text-sm leading-6 text-slate-600">
            Choisis un avatar prédéfini pour apparaître dans le dashboard, les résultats et les classements.
        </p>
    </header>

    <form method="post" action="{{ route('profile.avatar.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($avatars as $avatar)
                <label class="chronomots-avatar-option {{ old('avatar_slug', $selectedAvatar['slug']) === $avatar['slug'] ? 'chronomots-avatar-option--active' : '' }}">
                    <input
                        type="radio"
                        name="avatar_slug"
                        value="{{ $avatar['slug'] }}"
                        class="sr-only"
                        @checked(old('avatar_slug', $selectedAvatar['slug']) === $avatar['slug'])
                    >

                    <x-player-avatar :avatar="$avatar" :title="$avatar['name']" :subtitle="$avatar['description']" size="lg" stacked />
                </label>
            @endforeach
        </div>

        <x-input-error class="mt-2" :messages="$errors->get('avatar_slug')" />

        <div class="flex items-center gap-4">
            <x-primary-button>Enregistrer l’avatar</x-primary-button>

            @if (session('status') === 'avatar-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2200)"
                    class="text-sm text-slate-600"
                >Avatar mis à jour.</p>
            @endif
        </div>
    </form>
</section>
