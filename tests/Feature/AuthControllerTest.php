<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test login with invalid credentials.
     *
     * @return void
     */
    public function test_login_with_invalid_credentials()
    {
        // Create a user
        $user = User::factory()->create([
            'email' => 'test@user.com',
            'password' => Hash::make('Password@123'),
        ]);

        // Send login request with invalid password
        $response = $this->postJson('/api/login', [
            'email' => 'test@user.com',
            'password' => 'wrongpassword',
        ]);

        // Assert response status is 401 (Unauthorized)
        $response->assertStatus(401);

        // Assert the response message is 'Unauthorized'
        $response->assertJson([
            'message' => 'Unauthorized',
        ]);
    }

    /**
     * Test login with missing email or password.
     *
     * @return void
     */
    public function test_login_with_missing_credentials()
    {
        // Send login request with missing email
        $response = $this->postJson('/api/login', [
            'password' => 'Password@123',
        ]);

        // Assert response status is 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert validation error for email
        $response->assertJsonValidationErrors(['email']);

        // Send login request with missing password
        $response = $this->postJson('/api/login', [
            'email' => 'test@user.com',
        ]);

        // Assert response status is 422 (Unprocessable Entity)
        $response->assertStatus(422);

        // Assert validation error for password
        $response->assertJsonValidationErrors(['password']);
    }
}
