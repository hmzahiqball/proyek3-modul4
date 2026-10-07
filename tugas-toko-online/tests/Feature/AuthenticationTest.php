<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Masuk untuk membuka dashboard');
    }

    public function test_authenticated_user_is_redirected_from_login_to_dashboard(): void
    {
        $user = User::factory()->create([
            'username' => 'budi',
            'nama_lengkap' => 'Budi Santoso',
        ]);

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_invalid_credentials_return_generic_error(): void
    {
        User::factory()->create([
            'username' => 'budi',
            'password' => Hash::make('rahasia123'),
        ]);

        $this->from('/login')
            ->post('/login', [
                'username' => 'budi',
                'password' => 'salah',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['username' => 'Username atau password salah.']);
    }

    public function test_valid_credentials_regenerate_session_and_open_dashboard(): void
    {
        User::factory()->create([
            'username' => 'budi',
            'password' => 'rahasia123',
            'nama_lengkap' => 'Budi Santoso',
        ]);

        $response = $this->post('/login', [
            'username' => 'budi',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('username', 'budi')->first());
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_logout_invalidates_session_and_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['temporary' => 'value'])
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertFalse(session()->has('temporary'));
    }
}
