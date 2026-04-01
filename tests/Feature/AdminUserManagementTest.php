<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_super_admin_can_access_super_admin_list_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_super_admin_can_access_create_super_admin_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->get(route('admin.users.create'))
            ->assertOk();
    }

    public function test_alumni_cannot_access_create_super_admin_page(): void
    {
        $alumni = User::factory()->create();
        $target = User::factory()->admin()->create();

        $this
            ->actingAs($alumni)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this
            ->actingAs($alumni)
            ->get(route('admin.users.create'))
            ->assertForbidden();

        $this
            ->actingAs($alumni)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_new_super_admin_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'admin.baru@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User super admin berhasil ditambahkan.');

        $this->assertDatabaseHas('users', [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.com',
            'role' => User::ROLE_SUPERADMIN,
        ]);
    }

    public function test_super_admin_can_delete_other_super_admin_user(): void
    {
        $actor = User::factory()->admin()->create();
        $target = User::factory()->admin()->create([
            'email' => 'target.admin@example.com',
        ]);

        $this
            ->actingAs($actor)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User super admin berhasil dihapus.');

        $this->assertDatabaseMissing('users', [
            'id' => $target->id,
        ]);
    }

    public function test_super_admin_cannot_delete_own_account_from_admin_user_page(): void
    {
        $actor = User::factory()->admin()->create();
        User::factory()->admin()->create([
            'email' => 'second.admin@example.com',
        ]);

        $this
            ->actingAs($actor)
            ->delete(route('admin.users.destroy', $actor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Akun yang sedang aktif tidak bisa dihapus.');

        $this->assertDatabaseHas('users', [
            'id' => $actor->id,
        ]);
    }

    public function test_super_admin_cannot_delete_last_super_admin_user(): void
    {
        $actor = User::factory()->admin()->create();

        $this
            ->actingAs($actor)
            ->delete(route('admin.users.destroy', $actor))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Minimal harus ada satu super admin.');

        $this->assertDatabaseHas('users', [
            'id' => $actor->id,
        ]);
    }

    public function test_super_admin_can_update_alumni_access_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
        ]);

        $payload = User::defaultAlumniAccessPermissions();
        $payload['actions']['create'] = true;
        $payload['actions']['delete'] = true;
        $payload['features']['social_chat'] = false;
        $payload['features']['business_marketplace'] = false;

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.permissions.update', $alumni), $payload)
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Hak akses alumni berhasil diperbarui.');

        $alumni->refresh();

        $this->assertTrue((bool) data_get($alumni->access_permissions, 'actions.create'));
        $this->assertTrue((bool) data_get($alumni->access_permissions, 'actions.delete'));
        $this->assertFalse((bool) data_get($alumni->access_permissions, 'features.social_chat'));
        $this->assertFalse((bool) data_get($alumni->access_permissions, 'features.business_marketplace'));
    }

    public function test_super_admin_can_sync_default_permissions_for_alumni_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $alumniWithoutPermissions = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'access_permissions' => null,
        ]);

        $alumniWithPartialPermissions = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'access_permissions' => [
                'features' => [
                    'social_chat' => false,
                ],
            ],
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.users.permissions.sync-defaults'))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame(
            User::defaultAlumniAccessPermissions(),
            $alumniWithoutPermissions->fresh()->access_permissions
        );

        $this->assertFalse((bool) data_get($alumniWithPartialPermissions->fresh()->access_permissions, 'features.social_chat'));
        $this->assertTrue((bool) data_get($alumniWithPartialPermissions->fresh()->access_permissions, 'features.social_forum'));
    }

    public function test_super_admin_can_set_global_permissions_for_all_alumni_accounts(): void
    {
        $admin = User::factory()->admin()->create();

        $firstAlumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'access_permissions' => User::defaultAlumniAccessPermissions(),
        ]);

        $secondAlumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'access_permissions' => User::defaultAlumniAccessPermissions(),
        ]);

        $payload = User::defaultAlumniAccessPermissions();
        $payload['features']['social_forum'] = false;
        $payload['features']['career_jobs'] = false;
        $payload['actions']['create'] = true;

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.permissions.update-global'), $payload)
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertFalse((bool) data_get($firstAlumni->fresh()->access_permissions, 'features.social_forum'));
        $this->assertFalse((bool) data_get($secondAlumni->fresh()->access_permissions, 'features.career_jobs'));
        $this->assertTrue((bool) data_get($secondAlumni->fresh()->access_permissions, 'actions.create'));
    }

    public function test_global_permissions_are_saved_to_integration_settings_as_default_template(): void
    {
        $admin = User::factory()->admin()->create();

        $payload = User::defaultAlumniAccessPermissions();
        $payload['features']['business_mentorship'] = false;

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.permissions.update-global'), $payload)
            ->assertRedirect(route('admin.users.index'));

        $stored = IntegrationSetting::query()->value('default_alumni_permissions');

        $this->assertFalse((bool) data_get($stored, 'features.business_mentorship'));
    }
}
