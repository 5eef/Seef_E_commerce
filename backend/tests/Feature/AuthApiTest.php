<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function asSpa(): static
    {
        return $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
            'Accept' => 'application/json',
        ]);
    }

    public function test_customer_can_register_and_read_profile(): void
    {
        $response = $this->asSpa()->postJson(
            '/api/auth/register',
            [
                'name' => 'New Customer',
                'email' => ' NEW@EXAMPLE.COM ',
                'phone' => '0611111111',
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Account created successfully.'
            )
            ->assertJsonPath(
                'user.email',
                'new@example.com'
            )
            ->assertJsonPath(
                'user.role',
                'customer'
            )
            ->assertJsonPath(
                'user.status',
                'active'
            )
            ->assertJsonMissingPath(
                'user.password'
            )
            ->assertJsonMissingPath(
                'user.remember_token'
            );

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->asSpa()
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                'new@example.com'
            );
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $this->asSpa()
            ->postJson(
                '/api/auth/register',
                [
                    'name' => 'Other Customer',
                    'email' => 'DUPLICATE@example.com',
                    'password' => 'Password123',
                    'password_confirmation' => 'Password123',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'email'
            );
    }

    public function test_customer_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => 'Password123',
            'status' => 'active',
        ]);

        $response = $this->asSpa()->postJson(
            '/api/auth/login',
            [
                'email' => ' EXISTING@EXAMPLE.COM ',
                'password' => 'Password123',
                'remember' => true,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Authenticated successfully.'
            )
            ->assertJsonPath(
                'user.email',
                'existing@example.com'
            )
            ->assertJsonMissingPath(
                'user.password'
            );

        $this->assertAuthenticatedAs($user);

        $this->assertNotNull(
            $user->fresh()->last_login_at
        );
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'password' => 'Password123',
        ]);

        $this->asSpa()
            ->postJson(
                '/api/auth/login',
                [
                    'email' => 'existing@example.com',
                    'password' => 'WrongPassword123',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'email'
            );

        $this->assertGuest();
    }

    public function test_suspended_customer_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => 'Password123',
            'status' => 'suspended',
        ]);

        $this->asSpa()
            ->postJson(
                '/api/auth/login',
                [
                    'email' => 'suspended@example.com',
                    'password' => 'Password123',
                ]
            )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Account is inactive.',
            ]);

        $this->assertGuest();
    }

    public function test_disabled_customer_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'disabled@example.com',
            'password' => 'Password123',
            'status' => 'disabled',
        ]);

        $this->asSpa()
            ->postJson(
                '/api/auth/login',
                [
                    'email' => 'disabled@example.com',
                    'password' => 'Password123',
                ]
            )
            ->assertForbidden()
            ->assertJson([
                'message' => 'Account is inactive.',
            ]);

        $this->assertGuest();
    }

    public function test_guest_cannot_read_profile(): void
    {
        $this->asSpa()
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_authenticated_customer_can_logout(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $this->asSpa()
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Logged out successfully.',
            ]);

        $this->assertGuest('web');
    }

    public function test_forgot_password_does_not_reveal_if_email_exists(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $existingResponse = $this
            ->asSpa()
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => ' EXISTING@EXAMPLE.COM ',
                ]
            );

        $unknownResponse = $this
            ->asSpa()
            ->postJson(
                '/api/auth/forgot-password',
                [
                    'email' => 'unknown@example.com',
                ]
            );

        $existingResponse
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists for this email, a password reset link has been sent.',
            ]);

        $unknownResponse
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists for this email, a password reset link has been sent.',
            ]);

        $this->assertSame(
            $existingResponse->json('message'),
            $unknownResponse->json('message')
        );

        Notification::assertSentTo(
            $user,
            ResetPassword::class
        );
    }

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'OldPassword123',
        ]);

        $token = Password::broker()
            ->createToken($user);

        $this->asSpa()
            ->postJson(
                '/api/auth/reset-password',
                [
                    'token' => $token,
                    'email' => ' RESET@EXAMPLE.COM ',
                    'password' => 'NewPassword123',
                    'password_confirmation' => 'NewPassword123',
                ]
            )
            ->assertOk()
            ->assertJson([
                'message' => 'Password reset successfully.',
            ]);

        $this->assertTrue(
            Hash::check(
                'NewPassword123',
                $user->fresh()->password
            )
        );
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
        ]);

        $this->asSpa()
            ->postJson(
                '/api/auth/reset-password',
                [
                    'token' => 'invalid-token',
                    'email' => $user->email,
                    'password' => 'NewPassword123',
                    'password_confirmation' => 'NewPassword123',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'email'
            );
    }
}
