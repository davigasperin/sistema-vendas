<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $term = (string) $request->input('search');
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return CustomerResource::collection($query->orderBy('name')->paginate($perPage));
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $customer = $this->customerService->createCustomer($request->validated());

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(CustomerRequest $request, Customer $customer): CustomerResource
    {
        $this->authorize('update', $customer);

        $updated = $this->customerService->updateCustomer($customer, $request->validated());

        return new CustomerResource($updated);
    }

    public function search(Request $request): AnonymousResourceCollection
    {
        $term = (string) $request->input('q', '');

        $query = Customer::query();
        if (! empty($term)) {
            $query->where('name', 'like', "%{$term}%");
        }

        return CustomerResource::collection($query->orderBy('name')->limit(10)->get());
    }
}
