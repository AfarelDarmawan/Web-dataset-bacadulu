<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DataAccessRequest;
use App\Models\DatasetVariable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __invoke(Request $request): View
    {
        Auth::shouldUse('web');
        $user = Auth::guard('web')->user();
        $base = DataAccessRequest::where('user_id', $user->id);

        return view('user.profile', [
            'profileUser' => $user,
            'stats' => [
                'requests' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'available_variables' => DatasetVariable::where('is_active', true)
                    ->whereHas('dataset', fn ($query) => $query->published())
                    ->whereHas('observations', fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']))
                    ->count(),
            ],
            'recentRequests' => (clone $base)
                ->with('dataset.provider')
                ->latest()
                ->limit(6)
                ->get(),
            'latestVariables' => DatasetVariable::where('is_active', true)
                ->whereHas('dataset', fn ($query) => $query->published())
                ->whereHas('observations', fn ($query) => $query->whereIn('quality_status', ['reviewed', 'verified']))
                ->with('dataset.provider')
                ->latest()
                ->limit(4)
                ->get(),
        ]);
    }

    public function edit(Request $request): View
    {
        Auth::shouldUse('web');

        return view('user.profile-edit', [
            'profileUser' => Auth::guard('web')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Auth::shouldUse('web');
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:190'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $oldAvatar = $user->avatar;

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->forceFill($validated)->save();

        if ($request->hasFile('avatar') && $oldAvatar && ! str_starts_with($oldAvatar, 'http')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return redirect()->route('user.profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
