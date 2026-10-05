<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_customers(): void
    {
        Customer::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('customers.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Customers/Index')->has('customers'));
    }

    public function test_can_create_customer(): void
    {
        $payload = [
            'name' => 'Carlos Silva',
            'email' => 'carlos@empresa.com',
            'phone' => '(11) 99999-8888',
            'address' => 'Rua Principal, 123',
            'birth_date' => '1990-05-15',
        ];

        $response = $this->actingAs($this->user)->post(route('customers.store'), $payload);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'email' => 'carlos@empresa.com',
        ]);
    }

    public function test_can_update_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Nome Antigo']);

        $payload = [
            'name' => 'Nome Atualizado',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'address' => $customer->address,
            'birth_date' => '1995-10-20',
        ];

        $response = $this->actingAs($this->user)->put(route('customers.update', $customer), $payload);

        $response->assertRedirect(route('customers.index'));
        $this->assertEquals('Nome Atualizado', $customer->fresh()->name);
    }
}
