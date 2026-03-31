<?php

use App\Http\Controllers\AlumniController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NewsPostController;
use App\Http\Controllers\ProfileController;
use App\Models\Alumni;
use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

Route::get('/', function () {
    $stats = [
        'totalAlumni' => 0,
        'jumlahJurusan' => 0,
        'lulusanTahunIni' => 0,
    ];
    $newsPosts = [];
    $events = [];

    if (Schema::hasTable('alumni')) {
        $stats = [
            'totalAlumni' => Alumni::count(),
            'jumlahJurusan' => Alumni::query()->distinct('jurusan')->count('jurusan'),
            'lulusanTahunIni' => Alumni::where('tahun_lulus', now()->year)->count(),
        ];
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
        'canRegister' => Route::has('register'),
        'stats' => $stats,
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
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::prefix('admin')->middleware(['verified', 'admin'])->group(function (): void {
        Route::resource('alumni', AlumniController::class)
            ->parameters(['alumni' => 'alumni']);
        Route::resource('berita', NewsPostController::class)
            ->names('berita')
            ->parameters(['berita' => 'newsPost'])
            ->where(['newsPost' => '[0-9]+']);
        Route::resource('agenda', EventController::class)
            ->names('agenda')
            ->parameters(['agenda' => 'event'])
            ->where(['event' => '[0-9]+']);
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
