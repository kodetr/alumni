<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_news_and_event_management_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->get(route('berita.index'))
            ->assertOk();

        $this
            ->actingAs($admin)
            ->get(route('agenda.index'))
            ->assertOk();
    }

    public function test_alumni_role_cannot_access_news_and_event_management_pages(): void
    {
        $alumni = User::factory()->create();

        $this
            ->actingAs($alumni)
            ->get(route('berita.index'))
            ->assertForbidden();

        $this
            ->actingAs($alumni)
            ->get(route('agenda.index'))
            ->assertForbidden();
    }
}
