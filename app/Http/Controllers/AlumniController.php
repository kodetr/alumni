<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlumniRequest;
use App\Http\Requests\UpdateAlumniRequest;
use App\Models\Alumni;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AlumniController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $jurusan = $request->string('jurusan')->toString();
        $tahunLulus = $request->string('tahun_lulus')->toString();
        $status = $request->string('status')->toString();
        $perPageOptions = [20, 30, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);
        $statusOptions = ['all', 'active', 'blocked'];

        if ($tahunLulus !== '' && ! ctype_digit($tahunLulus)) {
            $tahunLulus = '';
        }

        if (! in_array($status, $statusOptions, true)) {
            $status = 'all';
        }

        if (! in_array($perPage, $perPageOptions, true)) {
            $perPage = 20;
        }

        $canMatchByNim = Schema::hasColumn('users', 'nim');
        $canMatchByEmail = Schema::hasColumn('users', 'email')
            && Schema::hasColumn('alumni', 'email_kampus')
            && Schema::hasColumn('alumni', 'email_pribadi');
        $hasBlockedFlag = Schema::hasColumn('users', 'is_blocked');

        $alumniQuery = Alumni::query()
            ->select(['id', 'nim', 'nama', 'jurusan', 'tahun_lulus', 'email_kampus', 'email_pribadi'])
            ->when($search, function ($query, $searchValue) {
                $query->where(function ($searchQuery) use ($searchValue): void {
                    $searchQuery
                        ->where('nama', 'like', "%{$searchValue}%")
                        ->orWhere('nim', 'like', "%{$searchValue}%")
                        ->orWhere('jurusan', 'like', "%{$searchValue}%");

                    if (ctype_digit((string) $searchValue)) {
                        $searchQuery->orWhere('tahun_lulus', (int) $searchValue);
                    }
                });
            })
            ->when($jurusan, fn ($query, $jurusanValue) => $query->where('jurusan', $jurusanValue))
            ->when($tahunLulus !== '', fn ($query) => $query->where('tahun_lulus', (int) $tahunLulus))
            ->when($status !== 'all', function (Builder $query) use ($status, $canMatchByNim, $canMatchByEmail, $hasBlockedFlag): void {
                if ($status === 'blocked') {
                    $this->applyAccountExistsFilter($query, $canMatchByNim, $canMatchByEmail, $hasBlockedFlag, true);

                    return;
                }

                if ($status === 'active') {
                    $this->applyNotBlockedFilter($query, $canMatchByNim, $canMatchByEmail, $hasBlockedFlag);
                }
            })
            ->orderByDesc('tahun_lulus')
            ->orderBy('nama');

        $alumni = $alumniQuery
            ->paginate($perPage)
            ->withQueryString();

        $accountMap = $this->buildAccountMap($alumni->getCollection(), $canMatchByNim, $canMatchByEmail, $hasBlockedFlag);

        $alumni->getCollection()->transform(function (Alumni $item) use ($accountMap): Alumni {
            $account = $accountMap->get($this->resolveAccountLookupKey($item));

            $item->has_user_account = $account !== null;
            $item->is_blocked = (bool) ($account?->is_blocked ?? false);

            unset($item->email_kampus, $item->email_pribadi);

            return $item;
        });

        return Inertia::render('Alumni/Index', [
            'alumni' => $alumni,
            'filters' => [
                'search' => $search,
                'jurusan' => $jurusan,
                'tahun_lulus' => $tahunLulus,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'perPageOptions' => $perPageOptions,
            'statusOptions' => $statusOptions,
            'tahunLulusOptions' => Alumni::query()
                ->whereNotNull('tahun_lulus')
                ->select('tahun_lulus')
                ->distinct()
                ->orderByDesc('tahun_lulus')
                ->pluck('tahun_lulus'),
            'jurusanOptions' => Alumni::query()
                ->select('jurusan')
                ->distinct()
                ->orderBy('jurusan')
                ->pluck('jurusan'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Alumni/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAlumniRequest $request): RedirectResponse
    {
        Alumni::create($request->validated());

        return to_route('alumni.index')->with('success', 'Data alumni berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Alumni $alumni): Response
    {
        return Inertia::render('Alumni/Show', [
            'alumni' => $alumni,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Alumni $alumni): Response
    {
        return Inertia::render('Alumni/Edit', [
            'alumni' => $alumni,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAlumniRequest $request, Alumni $alumni): RedirectResponse
    {
        $alumni->update($request->validated());

        return to_route('alumni.index')->with('success', 'Data alumni berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Alumni $alumni): RedirectResponse
    {
        $alumni->delete();

        return to_route('alumni.index')->with('success', 'Data alumni berhasil dihapus.');
    }

    public function toggleBlock(Request $request, Alumni $alumni): RedirectResponse
    {
        if (! Schema::hasColumn('users', 'is_blocked')) {
            return to_route('alumni.index')->with('success', 'Fitur blokir belum aktif. Jalankan migrasi terbaru terlebih dahulu.');
        }

        $validated = $request->validate([
            'blocked' => ['required', 'boolean'],
        ]);

        $isBlocked = (bool) $validated['blocked'];

        $user = $this->resolveAlumniUser($alumni);

        if (! $user) {
            $user = $this->createAlumniUser($alumni, $isBlocked);

            if (! $user) {
                return to_route('alumni.index')->with('success', 'Gagal membuat akun alumni otomatis. Lengkapi data NIM atau email alumni terlebih dahulu.');
            }
        }

        $user->forceFill([
            'is_blocked' => $isBlocked,
        ])->save();

        return to_route('alumni.index')->with(
            'success',
            $isBlocked
                ? "Akun alumni {$alumni->nama} berhasil diblokir."
                : "Blokir akun alumni {$alumni->nama} berhasil dibuka.",
        );
    }

    /**
     * @return Collection<string, User>
     */
    private function buildAccountMap(Collection $rows, bool $canMatchByNim, bool $canMatchByEmail, bool $hasBlockedFlag): Collection
    {
        if ($canMatchByNim) {
            $nims = $rows->pluck('nim')->filter()->values()->all();

            if ($nims === []) {
                return collect();
            }

            $query = User::query()
                ->select(['id', 'nim'])
                ->where('role', User::ROLE_ALUMNI)
                ->whereIn('nim', $nims);

            if ($hasBlockedFlag) {
                $query->addSelect('is_blocked');
            } else {
                $query->selectRaw('false as is_blocked');
            }

            return $query->get()
                ->keyBy(fn (User $user) => (string) $user->nim);
        }

        if ($canMatchByEmail) {
            $emails = $rows
                ->flatMap(fn (Alumni $row) => [$row->email_kampus, $row->email_pribadi])
                ->filter(fn ($email) => is_string($email) && trim($email) !== '')
                ->map(fn (string $email) => strtolower(trim($email)))
                ->unique()
                ->values()
                ->all();

            if ($emails === []) {
                return collect();
            }

            $query = User::query()
                ->select(['id', 'email'])
                ->where('role', User::ROLE_ALUMNI)
                ->whereIn('email', $emails);

            if ($hasBlockedFlag) {
                $query->addSelect('is_blocked');
            } else {
                $query->selectRaw('false as is_blocked');
            }

            return $query->get()
                ->keyBy(fn (User $user) => strtolower((string) $user->email));
        }

        return collect();
    }

    private function resolveAlumniUser(Alumni $alumni): ?User
    {
        if (Schema::hasColumn('users', 'nim')) {
            $user = User::query()
                ->where('role', User::ROLE_ALUMNI)
                ->where('nim', $alumni->nim)
                ->first();

            if ($user) {
                return $user;
            }
        }

        if (
            Schema::hasColumn('users', 'email')
            && Schema::hasColumn('alumni', 'email_kampus')
            && Schema::hasColumn('alumni', 'email_pribadi')
        ) {
            $emails = collect([$alumni->email_kampus, $alumni->email_pribadi])
                ->filter(fn ($email) => is_string($email) && trim($email) !== '')
                ->map(fn (string $email) => strtolower(trim($email)))
                ->values()
                ->all();

            if ($emails === []) {
                return null;
            }

            return User::query()
                ->where('role', User::ROLE_ALUMNI)
                ->whereIn('email', $emails)
                ->first();
        }

        return null;
    }

    private function createAlumniUser(Alumni $alumni, bool $blocked): ?User
    {
        $attributes = [];

        if (Schema::hasColumn('users', 'role')) {
            $attributes['role'] = User::ROLE_ALUMNI;
        }

        if (Schema::hasColumn('users', 'name')) {
            $name = trim((string) $alumni->nama);
            $attributes['name'] = $name !== '' ? $name : 'Alumni '.$alumni->nim;
        }

        if (Schema::hasColumn('users', 'nim')) {
            $nim = trim((string) $alumni->nim);

            if ($nim !== '') {
                $attributes['nim'] = $nim;
            }
        }

        if (Schema::hasColumn('users', 'tanggal_lahir')) {
            $attributes['tanggal_lahir'] = $alumni->tanggal_lahir?->toDateString();
        }

        if (Schema::hasColumn('users', 'email')) {
            $attributes['email'] = $this->resolveGeneratedEmail($alumni);

            if (! $attributes['email']) {
                return null;
            }
        }

        if (Schema::hasColumn('users', 'password')) {
            $attributes['password'] = Hash::make(Str::random(40));
        }

        if (Schema::hasColumn('users', 'is_blocked')) {
            $attributes['is_blocked'] = $blocked;
        }

        if (Schema::hasColumn('users', 'access_permissions')) {
            $attributes['access_permissions'] = User::globalAlumniAccessPermissions();
        }

        if ($attributes === []) {
            return null;
        }

        return User::query()->create($attributes);
    }

    private function resolveGeneratedEmail(Alumni $alumni): ?string
    {
        $candidates = collect([
            $alumni->email_kampus,
            $alumni->email_pribadi,
            trim((string) $alumni->nim) !== '' ? 'alumni.'.trim((string) $alumni->nim).'@local.alumni' : null,
        ])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => strtolower(trim($value)))
            ->unique()
            ->values();

        foreach ($candidates as $candidate) {
            if (! User::query()->where('email', $candidate)->exists()) {
                return $candidate;
            }
        }

        return null;
    }

    private function applyNotBlockedFilter(Builder $query, bool $canMatchByNim, bool $canMatchByEmail, bool $hasBlockedFlag): void
    {
        if (! $hasBlockedFlag) {
            return;
        }

        if ($canMatchByNim) {
            $query->whereNotExists(function ($subQuery): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('users')
                    ->where('users.role', User::ROLE_ALUMNI)
                    ->where('users.is_blocked', true)
                    ->whereColumn('users.nim', 'alumni.nim');
            });

            return;
        }

        if ($canMatchByEmail) {
            $query->whereNotExists(function ($subQuery): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('users')
                    ->where('users.role', User::ROLE_ALUMNI)
                    ->where('users.is_blocked', true)
                    ->where(function ($emailQuery): void {
                        $emailQuery
                            ->whereColumn('users.email', 'alumni.email_kampus')
                            ->orWhereColumn('users.email', 'alumni.email_pribadi');
                    });
            });
        }
    }

    private function resolveAccountLookupKey(Alumni $alumni): string
    {
        if (Schema::hasColumn('users', 'nim')) {
            return (string) $alumni->nim;
        }

        $emailKampus = is_string($alumni->email_kampus) ? strtolower(trim($alumni->email_kampus)) : '';

        if ($emailKampus !== '') {
            return $emailKampus;
        }

        return is_string($alumni->email_pribadi) ? strtolower(trim($alumni->email_pribadi)) : '';
    }

    private function applyAccountExistsFilter(Builder $query, bool $canMatchByNim, bool $canMatchByEmail, bool $hasBlockedFlag, bool $blocked): void
    {
        if (! $hasBlockedFlag) {
            if ($blocked) {
                $query->whereRaw('1 = 0');

                return;
            }

            $this->applyNoAccountFilter($query, $canMatchByNim, $canMatchByEmail, true);

            return;
        }

        if ($canMatchByNim) {
            $query->whereExists(function ($subQuery) use ($blocked): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('users')
                    ->where('users.role', User::ROLE_ALUMNI)
                    ->where('users.is_blocked', $blocked)
                    ->whereColumn('users.nim', 'alumni.nim');
            });

            return;
        }

        if ($canMatchByEmail) {
            $query->whereExists(function ($subQuery) use ($blocked): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('users')
                    ->where('users.role', User::ROLE_ALUMNI)
                    ->where('users.is_blocked', $blocked)
                    ->where(function ($emailQuery): void {
                        $emailQuery
                            ->whereColumn('users.email', 'alumni.email_kampus')
                            ->orWhereColumn('users.email', 'alumni.email_pribadi');
                    });
            });

            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function applyNoAccountFilter(Builder $query, bool $canMatchByNim, bool $canMatchByEmail, bool $negate = false): void
    {
        if ($canMatchByNim) {
            $method = $negate ? 'whereExists' : 'whereNotExists';

            $query->{$method}(function ($subQuery): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('users')
                    ->where('users.role', User::ROLE_ALUMNI)
                    ->whereColumn('users.nim', 'alumni.nim');
            });

            return;
        }

        if ($canMatchByEmail) {
            $method = $negate ? 'whereExists' : 'whereNotExists';

            $query->{$method}(function ($subQuery): void {
                $subQuery
                    ->selectRaw('1')
                    ->from('users')
                    ->where('users.role', User::ROLE_ALUMNI)
                    ->where(function ($emailQuery): void {
                        $emailQuery
                            ->whereColumn('users.email', 'alumni.email_kampus')
                            ->orWhereColumn('users.email', 'alumni.email_pribadi');
                    });
            });

            return;
        }

        $query->whereRaw('1 = 1');
    }
}
