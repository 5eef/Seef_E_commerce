<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CustomerIndexRequest;
use App\Http\Requests\Admin\UpdateCustomerStatusRequest;
use App\Http\Resources\Admin\AdminCustomerResource;
use App\Services\Admin\CustomerService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    public function index(
        CustomerIndexRequest $request
    ): AnonymousResourceCollection {
        $customers = $this
            ->customerService
            ->paginate(
                $request->validated()
            );

        return AdminCustomerResource::collection(
            $customers
        );
    }

    public function show(
        int $customer
    ): AdminCustomerResource {
        $customer = $this
            ->customerService
            ->findCustomerOrFail(
                $customer
            );

        Gate::authorize(
            'view',
            $customer
        );

        return new AdminCustomerResource(
            $customer
        );
    }

    public function updateStatus(
        UpdateCustomerStatusRequest $request,
        int $customer
    ): AdminCustomerResource {
        $customer = $this
            ->customerService
            ->findCustomerOrFail(
                $customer
            );

        Gate::authorize(
            'update',
            $customer
        );

        $customer = $this
            ->customerService
            ->updateStatus(
                $customer,
                $request->validated(
                    'status'
                )
            );

        return new AdminCustomerResource(
            $customer
        );
    }
}
