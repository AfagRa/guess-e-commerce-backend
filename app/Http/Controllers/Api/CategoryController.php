<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();

        $items = $categories->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'parent_id' => $c->parent_id,
            'children' => $c->children->map(fn (Category $child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'parent_id' => $child->parent_id,
            ])->values()->all(),
        ])->values()->all();

        return $this->success(['items' => $items], 'OK');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'parent_id' => $validated['parent_id'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->success([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'sort_order' => $category->sort_order,
        ], 'Created', 201);
    }

    public function update(Request $request, int $id)
    {
        $category = Category::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:categories,slug,'.$category->id,
            'parent_id' => 'nullable|integer|exists:categories,id',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (isset($validated['parent_id']) && (int) $validated['parent_id'] === $category->id) {
            return $this->error('Invalid parent', 422);
        }

        $category->fill($validated);
        $category->save();

        return $this->success([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'sort_order' => $category->sort_order,
        ], 'Updated');
    }

    public function destroy(int $id)
    {
        $category = Category::query()->findOrFail($id);
        Category::query()->where('parent_id', $category->id)->update(['parent_id' => null]);
        $category->delete();

        return $this->success(null, 'Deleted');
    }
}
