<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureSuperAdmin($request);

        $search = $request->string('search')->toString();

        $users = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->whereIn('role', [User::ROLE_SUPERADMIN, 'admin'])
            ->when($search, function ($query, $searchValue) {
                $query->where(function ($searchQuery) use ($searchValue): void {
                    $searchQuery
                        ->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                });
            })
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->ensureSuperAdmin($request);

        return Inertia::render('Users/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => User::ROLE_SUPERADMIN,
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User super admin berhasil ditambahkan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if (! $user->isSuperAdmin()) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'User bukan super admin.');
        }

        $totalSuperAdmins = User::query()
            ->whereIn('role', [User::ROLE_SUPERADMIN, 'admin'])
            ->count();

        if ($totalSuperAdmins <= 1) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Minimal harus ada satu super admin.');
        }

        if ((int) $request->user()?->id === (int) $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Akun yang sedang aktif tidak bisa dihapus.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User super admin berhasil dihapus.');
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
    }
}
