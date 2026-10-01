<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customerService)
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request): Response
    {
        $customers = $this->customerService->getCustomersPaginated(
            $request->filled('search') ? $request->search : null
        );

        $stats = $this->customerService->getStatistics();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'totalCustomers' => $stats['total'],
            'newThisMonth' => $stats['newThisMonth'],
            'recentCustomers' => $stats['recent'],
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create');
    }

    public function store(CustomerRequest $request)
    {
        $this->customerService->createCustomer($request->validated());

        return redirect()->route('customers.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Customer $customer): Response
    {
        $customer->load('sales.items.product');

        return Inertia::render('Customers/Show', compact('customer'));
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $this->customerService->updateCustomer($customer, $request->validated());

        return redirect()->route('customers.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Customer $customer)
    {
        $this->customerService->deleteCustomer($customer);

        return redirect()->route('customers.index')->with('success', 'Cliente excluído com sucesso!');
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->get('q', '');
        $customers = $this->customerService->search($term);

        return response()->json($customers);
    }

    public function searchApi(Request $request): JsonResponse
    {
        $term = $request->get('q', '');

        if (empty($term)) {
            $customers = Customer::orderBy('name')->limit(5)->get(['id', 'name']);
        } else {
            $customers = Customer::where('name', 'like', "%{$term}%")
                ->limit(10)
                ->get(['id', 'name']);
        }

        return response()->json($customers);
    }
}
