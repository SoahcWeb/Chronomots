<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @php
        $audioPreferences = auth()->check()
            ? auth()->user()->userPreference
            : (object) \App\Models\UserPreference::defaults();
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Chronomots') }} | Nethra Gaming</title>

        @include('layouts.partials.pwa-meta')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900|space-grotesk:500,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        class="chronomots-grid font-sans antialiased"
        data-audio-sound-enabled="{{ $audioPreferences->sound_enabled ? '1' : '0' }}"
        data-audio-music-enabled="{{ $audioPreferences->music_enabled ? '1' : '0' }}"
        data-audio-volume-level="{{ $audioPreferences->volume_level }}"
        data-audio-muted="0"
    >
        <div class="min-h-screen">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="px-4 pt-6 sm:px-6 lg:px-8">
                    <div class="chronomots-panel mx-auto max-w-7xl rounded-[1.75rem] px-5 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="pb-10">
                {{ $slot }}
            </main>

            @include('layouts.footer')
        </div>
    </body>
</html>
