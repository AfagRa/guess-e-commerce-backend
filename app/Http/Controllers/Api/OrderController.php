<?php

namespace App\Http\Controllers\Api;

use App\Models\BasketItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends BaseController
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])])
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 15));

        $items = $orders->getCollection()->map(fn (Order $o) => $this->formatOrder($o))->values()->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ], 'OK');
    }

    public function show(Request $request, int $id)
    {
        $order = Order::query()
            ->where('user_id', $request->user()->id)
            ->with(['items.product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])])
            ->where('id', $id)
            ->firstOrFail();

        return $this->success($this->formatOrder($order), 'OK');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
        ]);

        $userId = $request->user()->id;

        return DB::transaction(function () use ($validated, $userId) {
            $basketRows = BasketItem::query()
                ->where('user_id', $userId)
                ->with('product')
                ->get();

            if ($basketRows->isEmpty()) {
                return $this->error('Basket is empty', 422);
            }

            $total = 0;
            foreach ($basketRows as $row) {
                $product = $row->product;
                $unit = $product->sale_price !== null ? (float) $product->sale_price : (float) $product->price;
                $total += $unit * $row->quantity;
            }

            $order = Order::create([
                'user_id' => $userId,
                'status' => 'pending',
                'total' => round($total, 2),
                'shipping_address' => $validated['shipping_address'],
            ]);

            foreach ($basketRows as $row) {
                $product = $row->product;
                $unit = $product->sale_price !== null ? (float) $product->sale_price : (float) $product->price;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'color' => $row->color,
                    'size' => $row->size,
                    'quantity' => $row->quantity,
                    'price' => round($unit, 2),
                ]);
            }

            BasketItem::query()->where('user_id', $userId)->delete();

            $order->load(['items.product' => fn ($q) => $q->with(['images' => fn ($iq) => $iq->orderBy('sort_order')])]);

            return $this->success($this->formatOrder($order), 'Order placed', 201);
        });
    }

    private function formatOrder(Order $order): array
    {
        $lines = $order->items->map(function (OrderItem $line) {
            return [
                'id' => $line->id,
                'product_id' => $line->product_id,
                'color' => $line->color,
                'size' => $line->size,
                'quantity' => $line->quantity,
                'price' => (float) $line->price,
                'product' => ProductPresenter::summary($line->product),
            ];
        })->all();

        return [
            'id' => $order->id,
            'status' => $order->status,
            'total' => (float) $order->total,
            'shipping_address' => $order->shipping_address,
            'items' => $lines,
            'created_at' => $order->created_at?->toIso8601String(),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ];
    }
}
