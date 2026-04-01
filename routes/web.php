<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\AlumniMaintenanceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\IntegrationSettingsController;
use App\Http\Controllers\NewsPostController;
use App\Http\Controllers\ProfileController;
use App\Models\Alumni;
use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

Route::get('/', function () {
    $alumniSearch = request()->string('q')->trim()->toString();

    $stats = [
        'totalAlumni' => 0,
        'jumlahJurusan' => 0,
        'lulusanTahunIni' => 0,
    ];
    $alumniResults = [];
    $newsPosts = [];
    $events = [];

    $buildAlumniAvatar = static function (string $name): string {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        if ($initials === '') {
            $initials = 'AL';
        }

        $safeInitials = htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#0f172a"/><stop offset="100%" stop-color="#0369a1"/></linearGradient></defs><rect width="160" height="160" rx="22" fill="url(#g)"/><text x="80" y="93" text-anchor="middle" font-size="52" font-family="Arial, sans-serif" font-weight="700" fill="#e0f2fe">'.$safeInitials.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    };

    if (Schema::hasTable('alumni')) {
        $stats = [
            'totalAlumni' => Alumni::count(),
            'jumlahJurusan' => Alumni::query()->distinct('jurusan')->count('jurusan'),
            'lulusanTahunIni' => Alumni::where('tahun_lulus', now()->year)->count(),
        ];

        if ($alumniSearch !== '') {
            $alumniQuery = Alumni::query()
                ->select(['id', 'nama', 'nim', 'tahun_lulus']);

            $alumniResults = $alumniQuery
                ->where(function ($query) use ($alumniSearch): void {
                    $query
                        ->where('nama', 'like', "%{$alumniSearch}%")
                        ->orWhere('nim', 'like', "%{$alumniSearch}%");
                })
                ->orderByDesc('tahun_lulus')
                ->orderBy('nama')
                ->limit(12)
                ->get()
                ->map(fn (Alumni $alumni) => [
                    'id' => $alumni->id,
                    'photo' => $buildAlumniAvatar($alumni->nama),
                    'nim' => $alumni->nim,
                    'nama' => $alumni->nama,
                    'tahun_lulus' => $alumni->tahun_lulus,
                ])
                ->all();
        }
    }

    if (Schema::hasTable('news_posts')) {
        $newsPosts = NewsPost::query()
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'cover_image_path'])
            ->toArray();
    }

    if (Schema::hasTable('events')) {
        $events = Event::query()
            ->published()
            ->whereDate('event_date', '>=', now()->toDateString())
            ->orderBy('event_date')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'description', 'event_date', 'location', 'registration_url', 'poster_image_path'])
            ->toArray();
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'stats' => $stats,
        'alumniSearch' => $alumniSearch,
        'alumniResults' => $alumniResults,
        'newsPosts' => $newsPosts,
        'events' => $events,
    ]);
})->name('home');

Route::get('/admin', fn () => redirect()->route('admin.login'))
    ->name('admin.entry');

Route::get('/maintenance/alumni', [AlumniMaintenanceController::class, 'show'])
    ->name('maintenance.alumni');

Route::get('/dashboard', function () {
    $isAdmin = request()->user()?->isAdmin() ?? false;
    $requestedYear = request()->string('year')->trim()->toString();

    $totalAlumni = 0;
    $alumniTahunIni = 0;
    $trackingScopeTotal = 0;
    $trackingScopeLabel = 'Semua tahun';
    $availableYears = [];
    $selectedYear = null;
    $tracking = [
        'bekerja' => ['count' => 0, 'percentage' => 0],
        'kuliah_lanjut' => ['count' => 0, 'percentage' => 0],
        'wirausaha' => ['count' => 0, 'percentage' => 0],
        'lainnya' => ['count' => 0, 'percentage' => 0],
        'classified_count' => 0,
    ];

    if ($isAdmin && Schema::hasTable('alumni')) {
        $totalAlumni = Alumni::count();
        $alumniTahunIni = Alumni::where('tahun_lulus', now()->year)->count();
        $hasTahunLulusColumn = Schema::hasColumn('alumni', 'tahun_lulus');

        if ($hasTahunLulusColumn) {
            $availableYears = Alumni::query()
                ->whereNotNull('tahun_lulus')
                ->distinct()
                ->orderByDesc('tahun_lulus')
                ->pluck('tahun_lulus')
                ->map(fn ($year): int => (int) $year)
                ->filter(fn (int $year): bool => $year >= 1900 && $year <= now()->year + 10)
                ->values()
                ->all();

            if (preg_match('/^\d{4}$/', $requestedYear) === 1) {
                $yearCandidate = (int) $requestedYear;

                if (in_array($yearCandidate, $availableYears, true)) {
                    $selectedYear = $yearCandidate;
                    $trackingScopeLabel = (string) $yearCandidate;
                }
            }
        }

        $hasPekerjaanColumn = Schema::hasColumn('alumni', 'pekerjaan');
        $hasStatusBekerjaColumn = Schema::hasColumn('alumni', 'status_bekerja');
        $columns = ['id'];

        if ($hasTahunLulusColumn) {
            $columns[] = 'tahun_lulus';
        }

        if ($hasPekerjaanColumn) {
            $columns[] = 'pekerjaan';
        }

        if ($hasStatusBekerjaColumn) {
            $columns[] = 'status_bekerja';
        }

        $bucketCounts = [
            'bekerja' => 0,
            'kuliah_lanjut' => 0,
            'wirausaha' => 0,
            'lainnya' => 0,
        ];

        $containsAnyKeyword = static function (string $haystack, array $keywords): bool {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    return true;
                }
            }

            return false;
        };

        $trackingQuery = Alumni::query()->select($columns);

        if ($selectedYear !== null && $hasTahunLulusColumn) {
            $trackingQuery->where('tahun_lulus', $selectedYear);
        }

        $trackingCollection = $trackingQuery->get();
        $trackingScopeTotal = $trackingCollection->count();

        $trackingCollection
            ->each(function (Alumni $alumni) use (&$bucketCounts, $hasPekerjaanColumn, $hasStatusBekerjaColumn, $containsAnyKeyword): void {
                $pekerjaan = $hasPekerjaanColumn
                    ? strtolower(trim((string) ($alumni->pekerjaan ?? '')))
                    : '';
                $statusBekerja = $hasStatusBekerjaColumn
                    ? (bool) ($alumni->status_bekerja ?? false)
                    : false;

                $isWirausaha = $containsAnyKeyword($pekerjaan, [
                    'wirausaha',
                    'wiraswasta',
                    'entrepreneur',
                    'pemilik usaha',
                    'bisnis',
                    'umkm',
                    'startup',
                ]);
                $isKuliahLanjut = $containsAnyKeyword($pekerjaan, [
                    'kuliah',
                    'mahasiswa',
                    'lanjut studi',
                    'studi lanjut',
                    'pascasarjana',
                    'magister',
                    'doktor',
                    's2',
                    's3',
                    'residen',
                ]);
                $isNotWorking = $containsAnyKeyword($pekerjaan, [
                    'belum bekerja',
                    'tidak bekerja',
                    'mencari kerja',
                    'pengangguran',
                    'menganggur',
                ]);
                $isBekerja = $statusBekerja || (
                    $pekerjaan !== ''
                    && ! $isWirausaha
                    && ! $isKuliahLanjut
                    && ! $isNotWorking
                );

                if ($isWirausaha) {
                    $bucketCounts['wirausaha']++;

                    return;
                }

                if ($isKuliahLanjut) {
                    $bucketCounts['kuliah_lanjut']++;

                    return;
                }

                if ($isBekerja) {
                    $bucketCounts['bekerja']++;

                    return;
                }

                $bucketCounts['lainnya']++;
            });

        $classifiedCount = $bucketCounts['bekerja'] + $bucketCounts['kuliah_lanjut'] + $bucketCounts['wirausaha'];
        $toPercentage = static fn (int $count, int $total): float => $total > 0
            ? round(($count / $total) * 100, 1)
            : 0;

        $tracking = [
            'bekerja' => ['count' => $bucketCounts['bekerja'], 'percentage' => $toPercentage($bucketCounts['bekerja'], $trackingScopeTotal)],
            'kuliah_lanjut' => ['count' => $bucketCounts['kuliah_lanjut'], 'percentage' => $toPercentage($bucketCounts['kuliah_lanjut'], $trackingScopeTotal)],
            'wirausaha' => ['count' => $bucketCounts['wirausaha'], 'percentage' => $toPercentage($bucketCounts['wirausaha'], $trackingScopeTotal)],
            'lainnya' => ['count' => $bucketCounts['lainnya'], 'percentage' => $toPercentage($bucketCounts['lainnya'], $trackingScopeTotal)],
            'classified_count' => $classifiedCount,
        ];
    }

    return Inertia::render('Dashboard', [
        'isAdmin' => $isAdmin,
        'stats' => [
            'totalAlumni' => $totalAlumni,
            'alumniTahunIni' => $alumniTahunIni,
            'tracking' => $tracking,
            'trackingScopeTotal' => $trackingScopeTotal,
            'trackingScopeLabel' => $trackingScopeLabel,
        ],
        'filters' => [
            'year' => $selectedYear,
            'availableYears' => $availableYears,
        ],
    ]);
})->middleware(['auth', 'alumni.maintenance', 'alumni.active', 'verified'])->name('dashboard');

Route::middleware(['auth', 'alumni.maintenance', 'alumni.active'])->group(function () {
    Route::prefix('admin')->middleware(['verified', 'admin'])->group(function (): void {
        Route::resource('alumni', AlumniController::class)
            ->parameters(['alumni' => 'alumni']);
        Route::patch('alumni/{alumni}/block', [AlumniController::class, 'toggleBlock'])
            ->name('alumni.block');
        Route::resource('berita', NewsPostController::class)
            ->names('berita')
            ->parameters(['berita' => 'newsPost'])
            ->where(['newsPost' => '[0-9]+']);
        Route::resource('agenda', EventController::class)
            ->names('agenda')
            ->parameters(['agenda' => 'event'])
            ->where(['event' => '[0-9]+']);
        Route::get('users', [AdminUserController::class, 'index'])
            ->name('admin.users.index');
        Route::get('users/create', [AdminUserController::class, 'create'])
            ->name('admin.users.create');
        Route::post('users', [AdminUserController::class, 'store'])
            ->name('admin.users.store');
        Route::patch('users/permissions/global', [AdminUserController::class, 'updateGlobalPermissions'])
            ->name('admin.users.permissions.update-global');
        Route::post('users/permissions/sync-defaults', [AdminUserController::class, 'syncAlumniPermissions'])
            ->name('admin.users.permissions.sync-defaults');
        Route::patch('users/{user}/permissions', [AdminUserController::class, 'updatePermissions'])
            ->name('admin.users.permissions.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])
            ->name('admin.users.destroy');
        Route::get('pengaturan/integrasi', [IntegrationSettingsController::class, 'index'])
            ->name('settings.integration.index');
        Route::post('pengaturan/integrasi/save', [IntegrationSettingsController::class, 'saveConfig'])
            ->name('settings.integration.save');
        Route::post('pengaturan/integrasi/test', [IntegrationSettingsController::class, 'testConnection'])
            ->name('settings.integration.test');
        Route::post('pengaturan/integrasi/fetch', [IntegrationSettingsController::class, 'fetch'])
            ->name('settings.integration.fetch');
        Route::post('pengaturan/integrasi/simpan-alumni', [IntegrationSettingsController::class, 'storeAlumniPreview'])
            ->name('settings.integration.store-alumni');
        Route::post('pengaturan/maintenance', [IntegrationSettingsController::class, 'updateMaintenance'])
            ->name('settings.maintenance.update');
        Route::post('pengaturan/database/backup', [IntegrationSettingsController::class, 'backupDatabase'])
            ->name('settings.database.backup');
        Route::post('pengaturan/database/restore', [IntegrationSettingsController::class, 'restoreDatabase'])
            ->name('settings.database.restore');
        Route::post('pengaturan/database/import', [IntegrationSettingsController::class, 'importDatabase'])
            ->name('settings.database.import');
        Route::delete('pengaturan/database/delete', [IntegrationSettingsController::class, 'deleteBackup'])
            ->name('settings.database.delete');
        Route::get('pengaturan/database/download/{fileName}', [IntegrationSettingsController::class, 'downloadBackup'])
            ->where('fileName', '[A-Za-z0-9._-]+')
            ->name('settings.database.download');
    });

    Route::prefix('jejaring-sosial')->name('social.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('social.forum'))
            ->middleware('permission:features.social_forum')
            ->name('index');
        Route::get('/forum-diskusi', fn () => Inertia::render('Social/Forum'))
            ->middleware('permission:features.social_forum')
            ->name('forum');
        Route::get('/chat-antar-alumni', fn () => Inertia::render('Social/Chat'))
            ->middleware('permission:features.social_chat')
            ->name('chat');
        Route::get('/grup-angkatan-jurusan', fn () => Inertia::render('Social/Groups'))
            ->middleware('permission:features.social_groups')
            ->name('groups');
    });

    Route::prefix('karier')->name('career.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('career.jobs'))
            ->middleware('permission:features.career_jobs')
            ->name('index');
        Route::get('/posting-loker', fn () => Inertia::render('Career/Jobs'))
            ->middleware('permission:features.career_jobs')
            ->name('jobs');
        Route::get('/career-center', fn () => Inertia::render('Career/Center'))
            ->middleware('permission:features.career_center')
            ->name('center');
    });

    Route::prefix('event-alumni')->name('eventmenu.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('eventmenu.reunion'))
            ->middleware('permission:features.event_reunion')
            ->name('index');
        Route::get('/reuni', fn () => Inertia::render('EventMenu/Reunion'))
            ->middleware('permission:features.event_reunion')
            ->name('reunion');
        Route::get('/webinar-seminar', fn () => Inertia::render('EventMenu/Webinar'))
            ->middleware('permission:features.event_webinar')
            ->name('webinar');
        Route::get('/networking', fn () => Inertia::render('EventMenu/Networking'))
            ->middleware('permission:features.event_networking')
            ->name('networking');
        Route::get('/rsvp-pendaftaran', fn () => Inertia::render('EventMenu/Rsvp'))
            ->middleware('permission:features.event_rsvp')
            ->name('rsvp');
    });

    Route::prefix('mapping')->name('mapping.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('mapping.locations'))
            ->middleware('permission:features.mapping_locations')
            ->name('index');
        Route::get('/lokasi-alumni', fn () => Inertia::render('Mapping/Locations'))
            ->middleware('permission:features.mapping_locations')
            ->name('locations');
        Route::get('/sebaran-global', fn () => Inertia::render('Mapping/Global'))
            ->middleware('permission:features.mapping_global')
            ->name('global');
    });

    Route::prefix('donasi')->name('donation.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('donation.online'))
            ->middleware('permission:features.donation_online')
            ->name('index');
        Route::get('/online', fn () => Inertia::render('Donation/Online'))
            ->middleware('permission:features.donation_online')
            ->name('online');
        Route::get('/program-beasiswa', fn () => Inertia::render('Donation/Scholarship'))
            ->middleware('permission:features.donation_scholarship')
            ->name('scholarship');
        Route::get('/crowdfunding', fn () => Inertia::render('Donation/Crowdfunding'))
            ->middleware('permission:features.donation_crowdfunding')
            ->name('crowdfunding');
    });

    Route::prefix('bisnis')->name('business.')->group(function (): void {
        Route::get('/', fn () => redirect()->route('business.marketplace'))
            ->middleware('permission:features.business_marketplace')
            ->name('index');
        Route::get('/marketplace', fn () => Inertia::render('Business/Marketplace'))
            ->middleware('permission:features.business_marketplace')
            ->name('marketplace');
        Route::get('/kerjasama', fn () => Inertia::render('Business/Partnership'))
            ->middleware('permission:features.business_partnership')
            ->name('partnership');
        Route::get('/mentorship', fn () => Inertia::render('Business/Mentorship'))
            ->middleware('permission:features.business_mentorship')
            ->name('mentorship');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->middleware('permission:features.profile_edit')
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('permission:features.profile_edit')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/berita/{newsPost:slug}', function (NewsPost $newsPost) {
    abort_unless(
        $newsPost->is_published
            && ($newsPost->published_at === null || $newsPost->published_at->lte(now())),
        404,
    );

    return Inertia::render('Public/NewsShow', [
        'canLogin' => Route::has('login'),
        'newsPost' => $newsPost,
        'relatedNews' => NewsPost::query()
            ->published()
            ->whereKeyNot($newsPost->id)
            ->latest('published_at')
            ->latest('id')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'cover_image_path']),
    ]);
})->name('landing.berita.show');

Route::get('/agenda/{event:slug}', function (Event $event) {
    abort_unless($event->is_published, 404);

    return Inertia::render('Public/EventShow', [
        'canLogin' => Route::has('login'),
        'event' => $event,
        'upcomingEvents' => Event::query()
            ->published()
            ->whereDate('event_date', '>=', now()->toDateString())
            ->whereKeyNot($event->id)
            ->orderBy('event_date')
            ->limit(3)
            ->get(['id', 'title', 'slug', 'event_date', 'location', 'poster_image_path']),
    ]);
})->name('landing.agenda.show');

require __DIR__.'/auth.php';
