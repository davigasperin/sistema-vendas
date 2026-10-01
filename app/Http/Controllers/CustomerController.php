<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customerService) {}

    public function index(Request $request): View
    {
        $customers = $this->customerService->getCustomersPaginated(
            $request->filled('search') ? $request->search : null
        );

        $stats = $this->customerService->getStatistics();

        return view('customers.index', [
            'customers' => $customers,
            'totalCustomers' => $stats['total'],
            'newThisMonth' => $stats['newThisMonth'],
            'recentCustomers' => $stats['recent'],
        ]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(CustomerRequest $request)
    {
        $this->customerService->createCustomer($request->validated());

        return redirect()->route('customers.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Customer $customer): View
    {
        $customer->load('sales.items.product');
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
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