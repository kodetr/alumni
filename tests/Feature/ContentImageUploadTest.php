<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_news_cover_and_replace_it(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->post(route('berita.store'), [
                'title' => 'Berita dengan cover',
                'excerpt' => 'Ringkasan berita.',
                'content' => 'Konten berita.',
                'published_at' => now()->subDay()->toDateTimeString(),
                'is_published' => true,
                'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            ])
            ->assertRedirect(route('berita.index'));

        $newsPost = NewsPost::query()->firstOrFail();

        $this->assertNotNull($newsPost->cover_image_path);
        $this->assertTrue(Storage::disk('public')->exists($newsPost->cover_image_path));

        $oldPath = $newsPost->cover_image_path;

        $this
            ->actingAs($admin)
            ->post(route('berita.update', $newsPost), [
                '_method' => 'put',
                'title' => 'Berita dengan cover baru',
                'excerpt' => 'Ringkasan terbaru.',
                'content' => 'Konten terbaru.',
                'published_at' => now()->subDay()->toDateTimeString(),
                'is_published' => true,
                'remove_image' => false,
                'cover_image' => UploadedFile::fake()->image('cover-baru.png'),
            ])
            ->assertRedirect(route('berita.index'));

        $newsPost->refresh();

        $this->assertNotNull($newsPost->cover_image_path);
        $this->assertNotSame($oldPath, $newsPost->cover_image_path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertTrue(Storage::disk('public')->exists($newsPost->cover_image_path));
    }

    public function test_admin_can_upload_event_poster_and_remove_it(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->post(route('agenda.store'), [
                'title' => 'Agenda dengan poster',
                'description' => 'Deskripsi agenda.',
                'event_date' => now()->addWeek()->toDateString(),
                'location' => 'Kampus UB',
                'registration_url' => 'https://example.com/register',
                'is_published' => true,
                'poster_image' => UploadedFile::fake()->image('poster.jpg'),
            ])
            ->assertRedirect(route('agenda.index'));

        $event = Event::query()->firstOrFail();

        $this->assertNotNull($event->poster_image_path);
        $this->assertTrue(Storage::disk('public')->exists($event->poster_image_path));

        $posterPath = $event->poster_image_path;

        $this
            ->actingAs($admin)
            ->post(route('agenda.update', $event), [
                '_method' => 'put',
                'title' => 'Agenda tanpa poster',
                'description' => 'Deskripsi agenda terbaru.',
                'event_date' => now()->addWeek()->toDateString(),
                'location' => 'Kampus UB',
                'registration_url' => 'https://example.com/register',
                'is_published' => true,
                'remove_image' => true,
            ])
            ->assertRedirect(route('agenda.index'));

        $event->refresh();

        $this->assertNull($event->poster_image_path);
        $this->assertFalse(Storage::disk('public')->exists($posterPath));
    }
}
