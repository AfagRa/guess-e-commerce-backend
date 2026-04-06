<?php

namespace App\Http\Controllers\Api;

use App\Models\BasketItem;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;

class BasketController extends BaseController
{
    public function index(Request $request)
    {
        $items = BasketItem::query()
            ->where('user_id', $request->user()->id)
            ->with(['product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])])
            ->orderBy('id')
            ->get();

        $data = $items->map(function (BasketItem $row) {
            return [
                'id' => $row->id,
                'product_id' => $row->product_id,
                'color' => $row->color,
                'size' => $row->size,
                'quantity' => $row->quantity,
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
            'quantity' => 'nullable|integer|min:1',
        ]);

        $qty = $validated['quantity'] ?? 1;

        $item = BasketItem::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
                'color' => $validated['color'],
                'size' => $validated['size'],
            ],
            []
        );

        if ($item->wasRecentlyCreated) {
            $item->quantity = $qty;
        } else {
            $item->quantity += $qty;
        }
        $item->save();

        $item->load(['product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])]);

        return $this->success([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'color' => $item->color,
            'size' => $item->size,
            'quantity' => $item->quantity,
            'product' => ProductPresenter::summary($item->product),
        ], 'OK', $item->wasRecentlyCreated ? 201 : 200);
    }

    public function update(Request $request, int $id)
    {
        $item = BasketItem::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item->quantity = $validated['quantity'];
        $item->save();

        $item->load(['product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])]);

        return $this->success([
            'id' => $item->id,
            'product_id' => $item->product_id,
            'color' => $item->color,
            'size' => $item->size,
            'quantity' => $item->quantity,
            'product' => ProductPresenter::summary($item->product),
        ], 'Updated');
    }

    public function destroy(Request $request, int $id)
    {
        BasketItem::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return $this->success(null, 'Removed');
    }

    public function clear(Request $request)
    {
        BasketItem::query()->where('user_id', $request->user()->id)->delete();

        return $this->success(null, 'Cleared');
    }
}
