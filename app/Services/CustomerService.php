<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Collection;

class CustomerService
{
    public function getCustomersWithFilters(array $filters): Collection
    {
        $query = Customer::query();

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getCustomersPaginated(?string $search = null, int $perPage = 15)
    {
        $query = Customer::query();

        if (! empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getStatistics(): array
    {
        return [
            'total' => Customer::count(),
            'newThisMonth' => Customer::whereMonth('created_at', now()->month)->count(),
            'recent' => Customer::orderBy('created_at', 'desc')->limit(5)->get(),
        ];
    }

    public function search(string $term): Collection
    {
        if (empty($term)) {
            return Customer::orderBy('name')->limit(5)->get(['id', 'name']);
        }

        return Customer::where('name', 'like', "%{$term}%")
            ->limit(10)
            ->get(['id', 'name']);
    }

    public function createCustomer(array $data): Customer
    {
        return Customer::create($data);
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update($data);

        return $customer;
    }

    public function deleteCustomer(Customer $customer): void
    {
        $customer->delete();
    }
}
