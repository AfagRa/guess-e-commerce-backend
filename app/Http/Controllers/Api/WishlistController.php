<?php

namespace App\Http\Controllers\Api;

use App\Models\Wishlist;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;

class WishlistController extends BaseController
{
    public function index(Request $request)
    {
        $items = Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->with(['product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])])
            ->orderBy('id')
            ->get();

        $data = $items->map(function (Wishlist $row) {
            return [
                'id' => $row->id,
                'product_id' => $row->product_id,
                'color' => $row->color,
                'size' => $row->size,
                'product' => ProductPresenter::summary($row->product),
            ];
        })->all();

        return $this->success($data, 'OK');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'color' => 'required|string|max:255',
            'size' => 'required|string|max:255',
        ]);

        $item = Wishlist::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
                'color' => $validated['color'],
                'size' => $validated['size'],
            ]
        );

        $item->load(['product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])]);

        return $this->success([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'color' => $item->color,
            'size' => $item->size,
            'product' => ProductPresenter::summary($item->product),
        ], 'OK', $item->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, int $id)
    {
        Wishlist::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return $this->success(null, 'Removed');
    }
}
