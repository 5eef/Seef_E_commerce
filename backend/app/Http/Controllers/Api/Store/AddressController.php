<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StoreAddressRequest;
use App\Http\Requests\Store\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AddressController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Address::class);

        return AddressResource::collection(
            request()->user()->addresses()->latest('id')->get()
        );
    }

    public function store(StoreAddressRequest $request): AddressResource
    {
        $address = DB::transaction(function () use ($request): Address {
            $attributes = $request->validated();
            $this->clearOtherDefaults($request->user()->id, $attributes);

            return $request->user()->addresses()->create($attributes);
        });

        return new AddressResource($address);
    }

    public function show(Address $address): AddressResource
    {
        Gate::authorize('view', $address);

        return new AddressResource($address);
    }

    public function update(UpdateAddressRequest $request, Address $address): AddressResource
    {
        DB::transaction(function () use ($request, $address): void {
            $attributes = $request->validated();
            $this->clearOtherDefaults($address->user_id, $attributes, $address->id);
            $address->update($attributes);
        });

        return new AddressResource($address->refresh());
    }

    public function destroy(Address $address): Response
    {
        Gate::authorize('delete', $address);
        $address->delete();

        return response()->noContent();
    }

    /** @param array<string, mixed> $attributes */
    private function clearOtherDefaults(int $userId, array $attributes, ?int $exceptId = null): void
    {
        foreach (['is_default_shipping', 'is_default_billing'] as $field) {
            if (! ($attributes[$field] ?? false)) {
                continue;
            }

            Address::query()
                ->where('user_id', $userId)
                ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
                ->update([$field => false]);
        }
    }
}
