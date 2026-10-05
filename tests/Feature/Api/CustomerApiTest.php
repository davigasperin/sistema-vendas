<?php

namespace Tests\Feature\Api;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_customers_with_pagination(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/customers');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email', 'phone'],
            ],
            'meta',
        ]);
    }

    public function test_can_create_customer_via_api(): void
    {
        $payload = [
            'name' => 'Mariana Souza',
            'email' => 'mariana@tech.com',
            'phone' => '11999998888',
            'address' => 'Av Paulista, 1000',
            'birth_date' => '1992-08-10',
        ];

        $response = $this->postJson('/api/v1/customers', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Mariana Souza');
        $this->assertDatabaseHas('customers', ['email' => 'mariana@tech.com']);
    }
}
