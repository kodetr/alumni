<?php

namespace Tests\Feature;

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
}
