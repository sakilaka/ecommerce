<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerAddressRequest;
use App\Http\Resources\CustomerAddressResource;
use App\Models\CustomerAddress;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class CustomerAddressController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CustomerAddressResource::collection(
            request()->user()->addresses()->latest('is_default')->latest()->get(),
        );
    }

    public function store(StoreCustomerAddressRequest $request): CustomerAddressResource
    {
        $address = DB::transaction(function () use ($request): CustomerAddress {
            $validated = $request->validated();

            if (($validated['is_default'] ?? false) || ! $request->user()->addresses()->exists()) {
                $request->user()->addresses()->update(['is_default' => false]);
                $validated['is_default'] = true;
            }

            return $request->user()->addresses()->create($validated);
        });

        return CustomerAddressResource::make($address);
    }

    public function update(UpdateCustomerAddressRequest $request, CustomerAddress $address): CustomerAddressResource
    {
        $this->authorizeAddress($address);

        DB::transaction(function () use ($request, $address): void {
            $validated = $request->validated();

            if ($validated['is_default'] ?? false) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update($validated);
        });

        return CustomerAddressResource::make($address->refresh());
    }

    public function destroy(CustomerAddress $address): Response
    {
        $this->authorizeAddress($address);
        $address->delete();

        return response()->noContent();
    }

    private function authorizeAddress(CustomerAddress $address): void
    {
        if ($address->user_id !== request()->user()->id) {
            abort(404);
        }
    }
}
