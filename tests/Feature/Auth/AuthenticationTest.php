<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_superadmin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_superadmin_can_authenticate_using_admin_login_endpoint(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_superadmin_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->admin()->create();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_alumni_cannot_authenticate_through_admin_login_endpoint(): void
    {
        $alumni = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
        ]);

        $this->post('/admin/login', [
            'email' => $alumni->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_alumni_can_authenticate_using_nim_and_tanggal_lahir(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
        ]);

        $response = $this->post('/login', [
            'nim' => $user->nim,
            'tanggal_lahir' => $user->tanggal_lahir?->toDateString(),
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_alumni_login_fails_with_invalid_tanggal_lahir(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ALUMNI,
        ]);

        $this->post('/login', [
            'nim' => $user->nim,
            'tanggal_lahir' => now()->subYear()->toDateString(),
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
