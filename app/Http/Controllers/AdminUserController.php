<?php

namespace App\Http\Controllers;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureSuperAdmin($request);

        $search = trim($request->string('search')->toString());
        $hasAccessPermissionsColumn = Schema::hasColumn('users', 'access_permissions');

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

        $alumniAccountsCount = User::query()->where('role', User::ROLE_ALUMNI)->count();

        $permissionsMissingCount = 0;

        if ($hasAccessPermissionsColumn) {
            $permissionsMissingCount = User::query()
                ->where('role', User::ROLE_ALUMNI)
                ->whereNull('access_permissions')
                ->count();
        }

        return Inertia::render('Users/Index', [
            'users' => $users,
            'alumniAccountsCount' => $alumniAccountsCount,
            'globalPermissions' => User::globalAlumniAccessPermissions(),
            'filters' => [
                'search' => $search,
            ],
            'permissionsFeatureReady' => $hasAccessPermissionsColumn,
            'permissionsMissingCount' => $permissionsMissingCount,
            'permissionCatalog' => $this->permissionCatalog(),
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

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if (! Schema::hasColumn('users', 'access_permissions')) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Kolom access_permissions belum tersedia. Jalankan migration terlebih dahulu.');
        }

        if (! $user->isAlumni()) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Hak akses hanya bisa diatur untuk akun alumni.');
        }

        $validated = $request->validate($this->permissionValidationRules());

        $user->forceFill([
            'access_permissions' => $validated,
        ])->save();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Hak akses alumni berhasil diperbarui.');
    }

    public function updateGlobalPermissions(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if (! Schema::hasColumn('users', 'access_permissions')) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Kolom access_permissions belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $validated = $request->validate($this->permissionValidationRules());

        if (Schema::hasTable('integration_settings') && Schema::hasColumn('integration_settings', 'default_alumni_permissions')) {
            $setting = IntegrationSetting::query()->first();

            if (! $setting) {
                $setting = IntegrationSetting::query()->create([
                    'endpoint' => 'https://placeholder.integration.local',
                    'api_key' => 'placeholder',
                ]);
            }

            $setting->forceFill([
                'default_alumni_permissions' => $validated,
            ])->save();
        }

        $affected = User::query()
            ->where('role', User::ROLE_ALUMNI)
            ->update([
                'access_permissions' => $validated,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Hak akses global alumni berhasil diperbarui untuk {$affected} akun.");
    }

    public function syncAlumniPermissions(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);

        if (! Schema::hasColumn('users', 'access_permissions')) {
            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Kolom access_permissions belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $updatedCount = 0;

        User::query()
            ->where('role', User::ROLE_ALUMNI)
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$updatedCount): void {
                /** @var User $user */
                foreach ($users as $user) {
                    $resolved = User::mergeWithDefaultAlumniAccessPermissions($user->access_permissions);

                    if ($resolved === (is_array($user->access_permissions) ? $user->access_permissions : null)) {
                        continue;
                    }

                    $user->forceFill([
                        'access_permissions' => $resolved,
                    ])->save();

                    $updatedCount++;
                }
            });

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Sinkronisasi hak akses alumni selesai. {$updatedCount} akun diperbarui.");
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function permissionValidationRules(): array
    {
        $template = User::defaultAlumniAccessPermissions();
        $rules = [];

        $walker = function (array $current, string $prefix = '') use (&$walker, &$rules): void {
            foreach ($current as $key => $value) {
                $path = $prefix !== '' ? "{$prefix}.{$key}" : $key;

                if (is_array($value)) {
                    $walker($value, $path);

                    continue;
                }

                $rules[$path] = ['required', 'boolean'];
            }
        };

        $walker($template);

        return $rules;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function permissionCatalog(): array
    {
        return [
            [
                'group' => 'actions',
                'title' => 'Aksi Data',
                'items' => [
                    ['key' => 'create', 'label' => 'Tambah'],
                    ['key' => 'edit', 'label' => 'Edit'],
                    ['key' => 'delete', 'label' => 'Hapus'],
                ],
            ],
            [
                'group' => 'features',
                'title' => 'Fitur Alumni',
                'items' => [
                    ['key' => 'profile_edit', 'label' => 'Edit Profil'],
                    ['key' => 'social_forum', 'label' => 'Forum Diskusi'],
                    ['key' => 'social_chat', 'label' => 'Chat Alumni'],
                    ['key' => 'social_groups', 'label' => 'Grup Alumni'],
                    ['key' => 'career_jobs', 'label' => 'Posting Loker'],
                    ['key' => 'career_center', 'label' => 'Career Center'],
                    ['key' => 'event_reunion', 'label' => 'Event Reuni'],
                    ['key' => 'event_webinar', 'label' => 'Event Webinar'],
                    ['key' => 'event_networking', 'label' => 'Event Networking'],
                    ['key' => 'event_rsvp', 'label' => 'RSVP Event'],
                    ['key' => 'mapping_locations', 'label' => 'Lokasi Alumni'],
                    ['key' => 'mapping_global', 'label' => 'Sebaran Global'],
                    ['key' => 'donation_online', 'label' => 'Donasi Online'],
                    ['key' => 'donation_scholarship', 'label' => 'Program Beasiswa'],
                    ['key' => 'donation_crowdfunding', 'label' => 'Crowdfunding'],
                    ['key' => 'business_marketplace', 'label' => 'Marketplace'],
                    ['key' => 'business_partnership', 'label' => 'Kerjasama'],
                    ['key' => 'business_mentorship', 'label' => 'Mentorship'],
                ],
            ],
        ];
    }
}
