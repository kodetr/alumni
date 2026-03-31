<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AlumniController;
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

Route::get('/dashboard', function () {
    $isAdmin = request()->user()?->isAdmin() ?? false;

    $totalAlumni = 0;
    $alumniTahunIni = 0;

    if ($isAdmin && Schema::hasTable('alumni')) {
        $totalAlumni = Alumni::count();
        $alumniTahunIni = Alumni::where('tahun_lulus', now()->year)->count();
    }

    return Inertia::render('Dashboard', [
        'isAdmin' => $isAdmin,
        'stats' => [
            'totalAlumni' => $totalAlumni,
            'alumniTahunIni' => $alumniTahunIni,
        ],
    ]);
})->middleware(['auth', 'alumni.active', 'verified'])->name('dashboard');

Route::middleware(['auth', 'alumni.active'])->group(function () {
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

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
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
