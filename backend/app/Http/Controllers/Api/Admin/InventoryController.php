<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustInventoryRequest;
use App\Http\Resources\Admin\AdminInventoryResource;
use App\Models\Inventory;
use App\Services\Admin\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Inventory::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'low_stock' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        return AdminInventoryResource::collection($this->inventoryService->paginate($filters));
    }

    public function show(Inventory $inventory): AdminInventoryResource
    {
        Gate::authorize('view', $inventory);

        return new AdminInventoryResource($inventory->load(['variant.product', 'movements' => fn ($query) => $query->latest()->limit(20)]));
    }

    public function update(AdjustInventoryRequest $request, Inventory $inventory): AdminInventoryResource
    {
        return new AdminInventoryResource(
            $this->inventoryService->adjust($inventory, $request->user(), $request->validated())
        );
    }
}
