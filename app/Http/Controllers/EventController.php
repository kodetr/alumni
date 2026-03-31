<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $events = Event::query()
            ->when($search, function ($query, $searchValue) {
                $query->where(function ($searchQuery) use ($searchValue): void {
                    $searchQuery
                        ->where('title', 'like', "%{$searchValue}%")
                        ->orWhere('location', 'like', "%{$searchValue}%")
                        ->orWhere('description', 'like', "%{$searchValue}%");
                });
            })
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Events/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = [
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['title']),
        ];

        if ($request->hasFile('poster_image')) {
            $payload['poster_image_path'] = $request->file('poster_image')->store('event-posters', 'public');
        }

        unset($payload['poster_image'], $payload['remove_image']);

        Event::query()->create($payload);

        return to_route('agenda.index')->with('success', 'Agenda alumni berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event): Response
    {
        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Event $event): Response
    {
        return Inertia::render('Events/Edit', [
            'event' => $event,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $validated = $request->validated();
        $payload = [
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['title'], $event->id),
        ];

        $shouldRemoveImage = (bool) ($validated['remove_image'] ?? false);

        if ($shouldRemoveImage && $event->poster_image_path) {
            Storage::disk('public')->delete($event->poster_image_path);
            $payload['poster_image_path'] = null;
        }

        if ($request->hasFile('poster_image')) {
            if ($event->poster_image_path) {
                Storage::disk('public')->delete($event->poster_image_path);
            }

            $payload['poster_image_path'] = $request->file('poster_image')->store('event-posters', 'public');
        }

        unset($payload['poster_image'], $payload['remove_image']);

        $event->update($payload);

        return to_route('agenda.index')->with('success', 'Agenda alumni berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Event $event): RedirectResponse
    {
        if ($event->poster_image_path) {
            Storage::disk('public')->delete($event->poster_image_path);
        }

        $event->delete();

        return to_route('agenda.index')->with('success', 'Agenda alumni berhasil dihapus.');
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'agenda';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            Event::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
