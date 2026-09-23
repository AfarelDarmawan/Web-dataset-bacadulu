<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->where('role', 'researcher')
            ->withCount('accessRequests')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->trim().'%';
                $query->where(fn ($inner) => $inner->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('institution', 'like', $term));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function toggle(User $user): RedirectResponse
{
    abort_if(
        $user->isAdmin(),
        403,
        'Status akun administrator tidak dapat diubah di sini.'
    );

    $user->forceFill([
        'status' => $user->status === 'active'
            ? 'inactive'
            : 'active',
    ])->save();

    AuditService::record('user.status_changed', $user, [
        'status' => $user->status,
    ]);

    return back()->with(
        'success',
        'Status pengguna berhasil diperbarui.'
    );
}

}
