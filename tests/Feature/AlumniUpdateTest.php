<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_alumni_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = Alumni::query()->create([
            'nama' => 'Nama Lama',
            'nim' => '2300000999',
            'jurusan' => 'Teknik Industri',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('alumni.edit', $alumni))
            ->assertOk();
    }

    public function test_admin_can_update_nama_and_tanggal_lahir_alumni(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = Alumni::query()->create([
            'nama' => 'Nama Lama',
            'nim' => '2300001000',
            'jurusan' => 'Administrasi Publik',
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('alumni.update', $alumni), [
                'nama' => 'Nama Baru Alumni',
                'nim' => '2300001000',
                'jurusan' => 'Administrasi Publik',
                'tanggal_lahir' => '2001-05-10',
                'no_telepon' => null,
                'tahun_lulus' => null,
                'pekerjaan' => null,
                'alamat' => null,
            ]);

        $response
            ->assertRedirect(route('alumni.index'))
            ->assertSessionHas('success', 'Data alumni berhasil diperbarui.');

        $this->assertDatabaseHas('alumni', [
            'id' => $alumni->id,
            'nama' => 'Nama Baru Alumni',
        ]);

        $this->assertSame('2001-05-10', $alumni->fresh()->tanggal_lahir?->toDateString());
    }

    public function test_update_rejects_future_tanggal_lahir(): void
    {
        $admin = User::factory()->admin()->create();
        $alumni = Alumni::query()->create([
            'nama' => 'Nama Lama',
            'nim' => '2300001001',
            'jurusan' => 'Teknik Industri',
        ]);

        $this
            ->actingAs($admin)
            ->from(route('alumni.edit', $alumni))
            ->put(route('alumni.update', $alumni), [
                'nama' => 'Nama Lama',
                'nim' => '2300001001',
                'jurusan' => 'Teknik Industri',
                'tanggal_lahir' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('alumni.edit', $alumni))
            ->assertSessionHasErrors(['tanggal_lahir']);
    }
}
