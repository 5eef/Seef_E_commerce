<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishlistResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class WishlistController extends Controller
{
    public function show(Request $request): WishlistResource
    {
        return new WishlistResource($this->wishlist($request));
    }

    public function storeItem(Request $request): WishlistResource
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
        ]);

        $product = Product::query()->published()->findOrFail($validated['product_id']);
        $wishlist = $request->user()->wishlist()->firstOrCreate();
        $wishlist->items()->firstOrCreate(['product_id' => $product->id]);

        return new WishlistResource($this->load($wishlist));
    }

    public function destroyItem(Request $request, Product $product): Response
    {
        $wishlist = $request->user()->wishlist;

        if ($wishlist === null) {
            abort(404);
        }

        $deleted = $wishlist->items()->where('product_id', $product->id)->delete();
        abort_if($deleted === 0, 404);

        return response()->noContent();
    }

    private function wishlist(Request $request): Wishlist
    {
        return $this->load($request->user()->wishlist()->firstOrCreate());
    }

    private function load(Wishlist $wishlist): Wishlist
    {
        return $wishlist->load([
            'items' => fn ($query) => $query->latest('id')->with([
                'product' => fn ($query) => $query->published()->with([
                    'images' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'),
                    'variants' => fn ($query) => $query->where('is_active', true)->with('inventory')->orderBy('sort_order')->orderBy('id'),
                ]),
            ]),
        ]);
    }
}
