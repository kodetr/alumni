<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsPostRequest;
use App\Http\Requests\UpdateNewsPostRequest;
use App\Models\NewsPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class NewsPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $newsPosts = NewsPost::query()
            ->when($search, function ($query, $searchValue) {
                $query->where(function ($searchQuery) use ($searchValue): void {
                    $searchQuery
                        ->where('title', 'like', "%{$searchValue}%")
                        ->orWhere('excerpt', 'like', "%{$searchValue}%");
                });
            })
            ->when($status === 'published', fn ($query) => $query->where('is_published', true))
            ->when($status === 'draft', fn ($query) => $query->where('is_published', false))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('News/Index', [
            'newsPosts' => $newsPosts,
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
        return Inertia::render('News/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNewsPostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = [
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['title']),
        ];

        if ($request->hasFile('cover_image')) {
            $payload['cover_image_path'] = $request->file('cover_image')->store('news-images', 'public');
        }

        unset($payload['cover_image'], $payload['remove_image']);

        NewsPost::query()->create($payload);

        return to_route('berita.index')->with('success', 'Berita alumni berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(NewsPost $newsPost): Response
    {
        return Inertia::render('News/Show', [
            'newsPost' => $newsPost,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NewsPost $newsPost): Response
    {
        return Inertia::render('News/Edit', [
            'newsPost' => $newsPost,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNewsPostRequest $request, NewsPost $newsPost): RedirectResponse
    {
        $validated = $request->validated();
        $payload = [
            ...$validated,
            'slug' => $this->generateUniqueSlug($validated['title'], $newsPost->id),
        ];

        $shouldRemoveImage = (bool) ($validated['remove_image'] ?? false);

        if ($shouldRemoveImage && $newsPost->cover_image_path) {
            Storage::disk('public')->delete($newsPost->cover_image_path);
            $payload['cover_image_path'] = null;
        }

        if ($request->hasFile('cover_image')) {
            if ($newsPost->cover_image_path) {
                Storage::disk('public')->delete($newsPost->cover_image_path);
            }

            $payload['cover_image_path'] = $request->file('cover_image')->store('news-images', 'public');
        }

        unset($payload['cover_image'], $payload['remove_image']);

        $newsPost->update($payload);

        return to_route('berita.index')->with('success', 'Berita alumni berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NewsPost $newsPost): RedirectResponse
    {
        if ($newsPost->cover_image_path) {
            Storage::disk('public')->delete($newsPost->cover_image_path);
        }

        $newsPost->delete();

        return to_route('berita.index')->with('success', 'Berita alumni berhasil dihapus.');
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if ($baseSlug === '') {
            $baseSlug = 'berita';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            NewsPost::query()
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
