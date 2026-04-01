<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniAccessPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_alumni_without_social_chat_permission_gets_forbidden(): void
    {
        $permissions = User::defaultAlumniAccessPermissions();
        $permissions['features']['social_chat'] = false;

        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'access_permissions' => $permissions,
        ]);

        $this
            ->actingAs($alumni)
            ->get(route('social.chat'))
            ->assertForbidden();
    }

    public function test_alumni_with_social_chat_permission_can_access_chat_page(): void
    {
        $permissions = User::defaultAlumniAccessPermissions();
        $permissions['features']['social_chat'] = true;

        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'access_permissions' => $permissions,
        ]);

        $this
            ->actingAs($alumni)
            ->get(route('social.chat'))
            ->assertOk();
    }
}
