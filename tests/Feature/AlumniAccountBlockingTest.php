<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniAccountBlockingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_block_and_unblock_alumni_account(): void
    {
        $admin = User::factory()->admin()->create();

        $alumni = Alumni::query()->create([
            'nama' => 'Alumni Demo',
            'nim' => '2300000001',
            'jurusan' => 'Teknik Industri',
            'tahun_lulus' => 2026,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'nim' => '2300000001',
            'is_blocked' => false,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('alumni.block', $alumni), ['blocked' => true])
            ->assertRedirect(route('alumni.index'))
            ->assertSessionHas('success', 'Akun alumni Alumni Demo berhasil diblokir.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_blocked' => true,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('alumni.block', $alumni), ['blocked' => false])
            ->assertRedirect(route('alumni.index'))
            ->assertSessionHas('success', 'Blokir akun alumni Alumni Demo berhasil dibuka.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_blocked' => false,
        ]);
    }

    public function test_block_action_creates_alumni_account_when_missing(): void
    {
        $admin = User::factory()->admin()->create();

        $alumni = Alumni::query()->create([
            'nama' => 'Alumni Baru',
            'nim' => '2300000010',
            'jurusan' => 'Teknik Industri',
            'email_kampus' => 'alumni.baru@kampus.test',
            'tahun_lulus' => 2026,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('alumni.block', $alumni), ['blocked' => true])
            ->assertRedirect(route('alumni.index'))
            ->assertSessionHas('success', 'Akun alumni Alumni Baru berhasil diblokir.');

        $this->assertDatabaseHas('users', [
            'role' => User::ROLE_ALUMNI,
            'nim' => '2300000010',
            'email' => 'alumni.baru@kampus.test',
            'is_blocked' => true,
        ]);
    }

    public function test_alumni_role_cannot_block_other_alumni_account(): void
    {
        $actor = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
        ]);

        $alumni = Alumni::query()->create([
            'nama' => 'Alumni Demo',
            'nim' => '2300000002',
            'jurusan' => 'Teknik Industri',
            'tahun_lulus' => 2026,
        ]);

        User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'nim' => '2300000002',
            'is_blocked' => false,
        ]);

        $this
            ->actingAs($actor)
            ->patch(route('alumni.block', $alumni), ['blocked' => true])
            ->assertForbidden();
    }

    public function test_blocked_alumni_cannot_login(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_blocked' => true,
        ]);

        $this
            ->post('/login', [
                'nim' => $user->nim,
                'tanggal_lahir' => $user->tanggal_lahir?->toDateString(),
            ])
            ->assertSessionHasErrors([
                'nim' => 'Akun alumni Anda sedang diblokir oleh admin.',
            ]);

        $this->assertGuest();
    }

    public function test_blocked_alumni_with_existing_session_is_logged_out(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
            'is_blocked' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
