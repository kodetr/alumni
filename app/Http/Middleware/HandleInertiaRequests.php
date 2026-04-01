<?php

namespace App\Http\Middleware;

use App\Models\Alumni;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'integration' => [
                'menuDataConnection' => function () use ($request): ?array {
                    $user = $request->user();

                    if (! $user instanceof User || ! $user->isAdmin()) {
                        return null;
                    }

                    return Cache::get((string) config('integration.status_cache_key', 'integration.menu_data.connection_status'));
                },
            ],
            'notifications' => function () use ($request): array {
                $user = $request->user();

                if (! $user instanceof User) {
                    return [
                        'items' => [],
                        'unreadCount' => 0,
                        'eventCount' => 0,
                        'reminderCount' => 0,
                    ];
                }

                $items = [];
                $eventCount = 0;
                $reminderCount = 0;

                if (Schema::hasTable('events')) {
                    $upcomingEvents = Event::query()
                        ->published()
                        ->whereDate('event_date', '>=', now()->toDateString())
                        ->orderBy('event_date')
                        ->limit(3)
                        ->get(['id', 'title', 'slug', 'event_date', 'location']);

                    foreach ($upcomingEvents as $event) {
                        $items[] = [
                            'id' => 'event-'.$event->id,
                            'type' => 'event',
                            'title' => $event->title,
                            'message' => sprintf(
                                'Event %s di %s',
                                $event->event_date?->format('d M Y') ?? '-',
                                $event->location ?? 'lokasi akan diumumkan'
                            ),
                            'url' => route('landing.agenda.show', ['event' => $event->slug]),
                        ];
                    }

                    $eventCount = $upcomingEvents->count();
                }

                if (Schema::hasTable('alumni')) {
                    $alumni = null;

                    if (Schema::hasColumn('users', 'nim') && is_string($user->nim) && trim($user->nim) !== '') {
                        $alumni = Alumni::query()->where('nim', trim($user->nim))->first();
                    }

                    if (! $alumni && is_string($user->email) && trim($user->email) !== '') {
                        $normalizedEmail = strtolower(trim($user->email));
                        $alumni = Alumni::query()
                            ->whereRaw('LOWER(email_kampus) = ?', [$normalizedEmail])
                            ->orWhereRaw('LOWER(email_pribadi) = ?', [$normalizedEmail])
                            ->first();
                    }

                    if ($alumni) {
                        $missingFields = [];

                        if (! is_string($alumni->no_telepon) || trim($alumni->no_telepon) === '') {
                            $missingFields[] = 'No. Telepon';
                        }

                        if (! is_string($alumni->alamat) || trim($alumni->alamat) === '') {
                            $missingFields[] = 'Alamat';
                        }

                        if ($missingFields !== []) {
                            $items[] = [
                                'id' => 'reminder-profile-'.$alumni->id,
                                'type' => 'reminder',
                                'title' => 'Reminder update data',
                                'message' => 'Lengkapi data: '.implode(', ', $missingFields),
                                'url' => route('profile.edit'),
                            ];
                            $reminderCount++;
                        }

                        if ($alumni->updated_at && $alumni->updated_at->lt(now()->subMonths(6))) {
                            $items[] = [
                                'id' => 'reminder-stale-'.$alumni->id,
                                'type' => 'reminder',
                                'title' => 'Reminder update data',
                                'message' => 'Data alumni belum diperbarui lebih dari 6 bulan.',
                                'url' => route('profile.edit'),
                            ];
                            $reminderCount++;
                        }
                    }
                }

                return [
                    'items' => $items,
                    'unreadCount' => count($items),
                    'eventCount' => $eventCount,
                    'reminderCount' => $reminderCount,
                ];
            },
        ];
    }
}
