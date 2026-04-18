<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Register Tests ──────────────────────────────

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Ahmed Mohamed',
            'email'                 => 'ahmed@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
        ]);
    }

    public function test_register_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'exists@example.com']);

        $response = $this->postJson('/api/register', [
            'name'                  => 'Another User',
            'email'                 => 'exists@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_without_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    // ─── Login Tests ─────────────────────────────────

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('mypassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'mypassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    // ─── Logout Tests ────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout');

        $response->assertStatus(200);

        // التأكد إن الـ Token اتمسح من الـ DB
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->postJson('/api/logout')
            ->assertStatus(401);

        $this->getJson('/api/me')
            ->assertStatus(401);
    }

    // ─── Me Tests ────────────────────────────────────

    public function test_me_returns_authenticated_user_data(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->actingAs($user)
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'id'    => $user->id,
                'name'  => 'Test User',
                'email' => $user->email,
            ]);
    }
}
