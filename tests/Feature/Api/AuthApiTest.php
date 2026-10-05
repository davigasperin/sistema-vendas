<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_via_api_and_receives_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'vendedor@sistema.com',
            'password' => bcrypt('senha123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'vendedor@sistema.com',
            'password' => 'senha123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'role_label'],
                'token',
                'token_type',
            ],
            'message',
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_fetch_me_endpoint(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    public function test_user_can_logout_and_revokes_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/logout');

        $response->assertOk();
        $response->assertJson([
            'message' => 'Sessão encerrada com sucesso.',
        ]);
    }
}
