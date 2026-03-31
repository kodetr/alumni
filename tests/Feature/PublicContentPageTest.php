<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContentPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_news_can_be_viewed_publicly_by_slug(): void
    {
        $newsPost = NewsPost::query()->create([
            'title' => 'Berita Publik',
            'slug' => 'berita-publik',
            'excerpt' => 'Ringkasan berita publik.',
            'content' => 'Konten lengkap berita publik.',
            'published_at' => now()->subDay(),
            'is_published' => true,
        ]);

        $this
            ->get(route('landing.berita.show', $newsPost->slug))
            ->assertOk()
            ->assertSee('Berita Publik');
    }

    public function test_unpublished_or_scheduled_news_returns_not_found(): void
    {
        $draftNews = NewsPost::query()->create([
            'title' => 'Berita Draft',
            'slug' => 'berita-draft',
            'excerpt' => 'Ringkasan berita draft.',
            'content' => 'Konten berita draft.',
            'published_at' => now()->subDay(),
            'is_published' => false,
        ]);

        $scheduledNews = NewsPost::query()->create([
            'title' => 'Berita Terjadwal',
            'slug' => 'berita-terjadwal',
            'excerpt' => 'Ringkasan berita terjadwal.',
            'content' => 'Konten berita terjadwal.',
            'published_at' => now()->addDay(),
            'is_published' => true,
        ]);

        $this
            ->get(route('landing.berita.show', $draftNews->slug))
            ->assertNotFound();

        $this
            ->get(route('landing.berita.show', $scheduledNews->slug))
            ->assertNotFound();
    }

    public function test_published_event_can_be_viewed_publicly_by_slug(): void
    {
        $event = Event::query()->create([
            'title' => 'Agenda Publik',
            'slug' => 'agenda-publik',
            'description' => 'Deskripsi agenda publik.',
            'event_date' => now()->addDays(7)->toDateString(),
            'location' => 'Kampus UB',
            'registration_url' => null,
            'is_published' => true,
        ]);

        $this
            ->get(route('landing.agenda.show', $event->slug))
            ->assertOk()
            ->assertSee('Agenda Publik');
    }

    public function test_draft_event_returns_not_found(): void
    {
        $event = Event::query()->create([
            'title' => 'Agenda Draft',
            'slug' => 'agenda-draft',
            'description' => 'Deskripsi agenda draft.',
            'event_date' => now()->addDays(7)->toDateString(),
            'location' => 'Kampus UB',
            'registration_url' => null,
            'is_published' => false,
        ]);

        $this
            ->get(route('landing.agenda.show', $event->slug))
            ->assertNotFound();
    }
}
