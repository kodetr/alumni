<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_alumni_management_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get('/admin/alumni');

        $response->assertOk();
    }

    public function test_alumni_role_cannot_access_alumni_management_pages(): void
    {
        $alumni = User::factory()->create();

        $response = $this
            ->actingAs($alumni)
            ->get('/admin/alumni');

        $response->assertForbidden();
    }
}
