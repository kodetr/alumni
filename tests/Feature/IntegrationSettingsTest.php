<?php

namespace Tests\Feature;

use App\Models\Alumni;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegrationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_integration_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->get(route('settings.integration.index'))
            ->assertOk();
    }

    public function test_alumni_cannot_access_integration_settings_page(): void
    {
        $alumni = User::factory()->create();

        $this
            ->actingAs($alumni)
            ->get(route('settings.integration.index'))
            ->assertForbidden();
    }

    public function test_admin_can_fetch_menu_data_from_integration_api(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            'http://127.0.0.1:8001/api/integration/alumni/menu-data' => Http::response([
                'menus' => [
                    ['name' => 'Dashboard', 'path' => '/dashboard'],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.fetch'), [
                'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                'api_key' => 'alumni_test_api_key',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('integrationResult')
            ->assertSessionHas('success', 'Data alumni berhasil diambil dari API (0 data).');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://127.0.0.1:8001/api/integration/alumni/menu-data'
                && $request->hasHeader('X-API-KEY', 'alumni_test_api_key')
                && $request->hasHeader('Authorization', 'Bearer alumni_test_api_key');
        });
    }

    public function test_admin_can_test_connection_before_fetching_data(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            'http://127.0.0.1:8001/api/integration/alumni/menu-data' => Http::response([
                'message' => 'ok',
            ], 200),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.test'), [
                'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                'api_key' => 'alumni_test_api_key',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('integrationTest', fn ($payload) => $payload['ok'] === true && $payload['status'] === 200);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://127.0.0.1:8001/api/integration/alumni/menu-data'
                && $request->hasHeader('X-API-KEY', 'alumni_test_api_key')
                && $request->hasHeader('Authorization', 'Bearer alumni_test_api_key');
        });

        $this->assertDatabaseHas('integration_settings', [
            'id' => 1,
            'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
        ]);
    }

    public function test_fetch_aggregates_all_pagination_pages_before_preview(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            'http://127.0.0.1:8001/api/integration/alumni/menu-data?page=2' => Http::response([
                'pagination' => [
                    'current_page' => 2,
                    'last_page' => 2,
                    'path' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                    'data' => [
                        [
                            'nim' => '2300000002',
                            'full_name' => 'Wisudawan 2',
                            'study_program_name' => 'Administrasi Publik',
                        ],
                    ],
                ],
            ], 200),
            'http://127.0.0.1:8001/api/integration/alumni/menu-data' => Http::response([
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 2,
                    'path' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                    'data' => [
                        [
                            'nim' => '2300000001',
                            'full_name' => 'Wisudawan 1',
                            'study_program_name' => 'Teknik Industri',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.fetch'), [
                'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                'api_key' => 'alumni_test_api_key',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('success', 'Data alumni berhasil diambil dari API (2 data).')
            ->assertSessionHas('integrationResult', function (array $payload): bool {
                $rows = $payload['data']['pagination']['data'] ?? [];

                return is_array($rows) && count($rows) === 2;
            });

        Http::assertSentCount(2);
    }

    public function test_admin_can_save_endpoint_and_api_key_configuration(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.save'), [
                'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                'api_key' => 'alumni_new_saved_key',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('success', 'Endpoint API dan API key berhasil disimpan.');

        $this->assertDatabaseHas('integration_settings', [
            'id' => 1,
            'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
        ]);
    }

    public function test_admin_gets_connection_test_error_when_api_is_unreachable(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            '*' => Http::response(null, 503),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.test'), [
                'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                'api_key' => 'alumni_test_api_key',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('integrationTest', fn ($payload) => $payload['ok'] === false && $payload['status'] === 503);
    }

    public function test_admin_gets_error_message_when_integration_api_returns_error(): void
    {
        $admin = User::factory()->admin()->create();

        Http::fake([
            '*' => Http::response([
                'message' => 'API key tidak valid.',
            ], 401),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.fetch'), [
                'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
                'api_key' => 'api_key_salah',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('integrationError', 'API key tidak valid.')
            ->assertSessionHas('integrationStatus', 401);

        $this->assertDatabaseHas('integration_settings', [
            'id' => 1,
            'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
        ]);
    }

    public function test_admin_can_delete_backup_sql_file(): void
    {
        $admin = User::factory()->admin()->create();

        Storage::disk('local')->put('backups/sql/backup_test.sql', '-- test backup');

        $response = $this
            ->actingAs($admin)
            ->delete(route('settings.database.delete'), [
                'file_name' => 'backup_test.sql',
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('success', 'File backup backup_test.sql berhasil dihapus.');

        $this->assertFalse(Storage::disk('local')->exists('backups/sql/backup_test.sql'));
    }

    public function test_admin_can_store_preview_data_to_alumni_table(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('settings.integration.store-alumni'), [
                'records' => [
                    [
                        'nim' => '2300000001',
                        'nama' => 'Wisudawan 1',
                        'jurusan' => 'Teknik Industri',
                        'angkatan' => 2023,
                        'email' => 'wisudawan1@example.com',
                        'photo_url' => 'https://cdn.example.com/photos/2300000001.jpg',
                        'no_telepon' => '08123456789',
                        'tahun_lulus' => 2026,
                        'pekerjaan' => null,
                        'instansi' => null,
                        'alamat' => 'Bandung',
                        'integration_payload' => [
                            'full_name' => 'Wisudawan 1',
                            'photo_path' => '/storage/photos/2300000001.jpg',
                        ],
                    ],
                    [
                        'nim' => '2300000002',
                        'nama' => 'Wisudawan 2',
                        'jurusan' => 'Administrasi Publik',
                        'angkatan' => 2022,
                        'email' => null,
                        'no_telepon' => null,
                        'tahun_lulus' => 2026,
                        'pekerjaan' => null,
                        'instansi' => null,
                        'alamat' => null,
                        'integration_payload' => [
                            'full_name' => 'Wisudawan 2',
                        ],
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('settings.integration.index'))
            ->assertSessionHas('success', '2 data alumni berhasil disimpan. (2 baru, 0 diperbarui)');

        $this->assertDatabaseHas('alumni', [
            'nim' => '2300000001',
            'nama' => 'Wisudawan 1',
            'jurusan' => 'Teknik Industri',
            'angkatan' => 2023,
            'tahun_lulus' => 2026,
            'photo_url' => 'https://cdn.example.com/photos/2300000001.jpg',
        ]);

        $this->assertDatabaseCount('alumni', 2);

        $this->assertSame(2, Alumni::query()->count());
    }
}
