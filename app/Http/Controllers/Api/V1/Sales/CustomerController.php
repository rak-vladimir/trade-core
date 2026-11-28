<?php

namespace App\Http\Controllers\Api\V1\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sales\IndexCustomerRequest;
use App\Http\Resources\Api\V1\Sales\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    public function index(IndexCustomerRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $customers = Customer::query()
            ->when(
                $filters['search'] ?? null,
                fn($query, $search) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('phone', 'ilike', "%{$search}%");
                })
            )
            ->when(
                array_key_exists('is_active', $filters),
                fn($query) => $query->where('is_active', $filters['is_active'])
            )
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15);

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer): CustomerResource
    {
        return new CustomerResource($customer);
    }
}
