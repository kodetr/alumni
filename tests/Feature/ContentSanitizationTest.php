<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_content_is_sanitized_before_saved(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->post(route('berita.store'), [
                'title' => '<b>Berita Aman</b>',
                'excerpt' => '<strong>Ringkasan</strong><script>alert(1)</script>',
                'content' => '<h2 onclick="alert(1)">Update</h2><p>Halo<script>alert(1)</script><a href="javascript:alert(2)">klik</a><a href="https://example.com" target="_blank">aman</a></p>',
                'published_at' => now()->subDay()->toDateTimeString(),
                'is_published' => true,
            ])
            ->assertRedirect(route('berita.index'));

        $newsPost = NewsPost::query()->firstOrFail();

        $this->assertSame('Berita Aman', $newsPost->title);
        $this->assertSame('Ringkasan', $newsPost->excerpt);
        $this->assertStringContainsString('<h2>Update</h2>', $newsPost->content ?? '');
        $this->assertStringContainsString('<a>klik</a>', $newsPost->content ?? '');
        $this->assertStringContainsString('<a href="https://example.com" target="_blank" rel="noopener noreferrer">aman</a>', $newsPost->content ?? '');
        $this->assertStringNotContainsString('<script', $newsPost->content ?? '');
        $this->assertStringNotContainsString('onclick=', $newsPost->content ?? '');
        $this->assertStringNotContainsString('javascript:', $newsPost->content ?? '');
    }

    public function test_event_description_is_sanitized_before_saved(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->post(route('agenda.store'), [
                'title' => '<b>Agenda Alumni</b>',
                'description' => '<p>Deskripsi <img src="x" onerror="alert(1)"><iframe src="https://evil.test"></iframe><a href="mailto:info@ub.ac.id">kontak</a></p>',
                'event_date' => now()->addWeek()->toDateString(),
                'location' => '<script>bad()</script>Aula Kampus',
                'registration_url' => 'https://example.com/register',
                'is_published' => true,
            ])
            ->assertRedirect(route('agenda.index'));

        $event = Event::query()->firstOrFail();

        $this->assertSame('Agenda Alumni', $event->title);
        $this->assertSame('Aula Kampus', $event->location);
        $this->assertStringContainsString('Deskripsi', $event->description ?? '');
        $this->assertStringContainsString('<a href="mailto:info@ub.ac.id">kontak</a>', $event->description ?? '');
        $this->assertStringNotContainsString('<iframe', $event->description ?? '');
        $this->assertStringNotContainsString('<img', $event->description ?? '');
        $this->assertStringNotContainsString('onerror=', $event->description ?? '');
    }
}
