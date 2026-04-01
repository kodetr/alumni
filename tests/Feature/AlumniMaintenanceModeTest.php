<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlumniMaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enable_maintenance_mode_for_alumni(): void
    {
        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->post(route('settings.maintenance.update'), [
                'enabled' => true,
                'duration_minutes' => 90,
            ])
            ->assertRedirect(route('settings.integration.index'));

        $this->assertDatabaseHas('integration_settings', [
            'id' => 1,
            'maintenance_enabled' => true,
        ]);
    }

    public function test_alumni_login_page_redirects_to_maintenance_when_active(): void
    {
        IntegrationSetting::query()->create([
            'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
            'api_key' => 'test-key',
            'maintenance_enabled' => true,
            'maintenance_ends_at' => now()->addMinutes(60),
        ]);

        $this
            ->get(route('login'))
            ->assertRedirect(route('maintenance.alumni'));
    }

    public function test_authenticated_alumni_is_forced_to_maintenance_page_when_active(): void
    {
        IntegrationSetting::query()->create([
            'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
            'api_key' => 'test-key',
            'maintenance_enabled' => true,
            'maintenance_ends_at' => now()->addMinutes(60),
        ]);

        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
        ]);

        $this
            ->actingAs($alumni)
            ->get(route('dashboard'))
            ->assertRedirect(route('maintenance.alumni'));

        $this->assertGuest();
    }

    public function test_admin_is_not_blocked_by_alumni_maintenance_mode(): void
    {
        IntegrationSetting::query()->create([
            'endpoint' => 'http://127.0.0.1:8001/api/integration/alumni/menu-data',
            'api_key' => 'test-key',
            'maintenance_enabled' => true,
            'maintenance_ends_at' => now()->addMinutes(60),
        ]);

        $admin = User::factory()->admin()->create();

        $this
            ->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
