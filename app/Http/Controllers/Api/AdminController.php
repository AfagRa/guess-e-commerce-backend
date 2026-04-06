<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;

class AdminController extends BaseController
{
    public function dashboard()
    {
        $revenue = (float) Order::query()->where('status', '!=', 'cancelled')->sum('total');

        return $this->success([
            'total_products' => Product::query()->count(),
            'total_users' => User::query()->count(),
            'total_orders' => Order::query()->count(),
            'total_revenue' => $revenue,
        ], 'OK');
    }

    public function products(Request $request)
    {
        $query = Product::query()->with(['images' => function ($q) {
            $q->orderBy('sort_order');
        }]);

        if ($request->filled('search')) {
            $term = '%'.$request->string('search')->toString().'%';
            $query->where('name', 'like', $term);
        }

        $paginator = $query->orderByDesc('id')->paginate(15)->withQueryString();

        $items = $paginator->getCollection()->map(fn (Product $p) => ProductPresenter::summary($p))->values()->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'OK');
    }

    public function users(Request $request)
    {
        $paginator = User::query()
            ->orderByDesc('id')
            ->paginate((int) $request->input('per_page', 15));

        $items = $paginator->getCollection()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role,
            'created_at' => $u->created_at?->toIso8601String(),
        ])->values()->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'OK');
    }

    public function updateUserRole(Request $request, int $id)
    {
        $validated = $request->validate([
            'role' => 'required|in:user,admin,superadmin',
        ]);

        $user = User::query()->findOrFail($id);

        if ($user->id === $request->user()->id) {
            return $this->error('Cannot change your own role', 422);
        }

        $user->role = $validated['role'];
        $user->save();

        return $this->success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ], 'Updated');
    }

    public function deleteUser(int $id)
    {
        $user = User::query()->findOrFail($id);
        if ($user->id === request()->user()->id) {
            return $this->error('Cannot delete yourself', 422);
        }
        $user->delete();
        return $this->success(null, 'Deleted');
    }

    public function orders(Request $request)
    {
        $query = Order::query()->with('user')->orderByDesc('id');

        if ($request->filled('search')) {
            $term = '%'.$request->string('search')->toString().'%';
            $query->whereHas('user', fn ($q) => $q->where('email', 'like', $term));
        }

        $paginator = $query->paginate((int) $request->input('per_page', 15))->withQueryString();

        $items = $paginator->getCollection()->map(fn (Order $o) => [
            'id' => $o->id,
            'user_id' => $o->user_id,
            'user_email' => $o->user?->email,
            'status' => $o->status,
            'total' => (float) $o->total,
            'created_at' => $o->created_at?->toIso8601String(),
        ])->values()->all();

        return $this->success([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'OK');
    }

    public function updateOrderStatus(Request $request, int $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::query()->findOrFail($id);
        $order->status = $validated['status'];
        $order->save();

        return $this->success([
            'id' => $order->id,
            'status' => $order->status,
        ], 'Updated');
    }
}
