<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\PlayerProfile;
use App\Models\UserPreference;
use App\Services\AvatarCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AvatarCatalogService $avatarCatalogService,
    ) {
    }

    /**
     * Display the user's profile overview.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('profile.show', [
            'user' => $user,
            'avatar' => $this->avatarCatalogService->avatarForUser($user),
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'preferences' => $user->userPreference,
            'avatars' => $this->avatarCatalogService->avatars(),
            'selectedAvatar' => $this->avatarCatalogService->avatarForUser($user),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.show')->with('status', 'profile-updated');
    }

    /**
     * Update the user's audio preferences.
     */
    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sound_enabled' => ['nullable', 'boolean'],
            'music_enabled' => ['nullable', 'boolean'],
            'volume_level' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $preferences = $request->user()->userPreference()->firstOrCreate([], UserPreference::defaults());

        $preferences->fill([
            'sound_enabled' => $request->boolean('sound_enabled'),
            'music_enabled' => $request->boolean('music_enabled'),
            'volume_level' => (int) $validated['volume_level'],
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'audio-preferences-updated');
    }

    /**
     * Update the user's preset avatar choice.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar_slug' => ['required', 'string', Rule::in($this->avatarCatalogService->avatarSlugs())],
        ]);

        $profile = $request->user()->playerProfile()->firstOrCreate([], PlayerProfile::defaults());

        $profile->fill([
            'avatar_type' => 'preset',
            'avatar_slug' => $validated['avatar_slug'],
        ])->save();

        return Redirect::route('profile.edit')->with('status', 'avatar-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
