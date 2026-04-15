<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ProductPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['images' => function ($q) {
            $q->orderBy('sort_order');
        }]);

        if ($request->filled('category')) {
            $query->whereJsonContains('category_path', $request->string('category')->toString());
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->string('gender')->toString());
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search')->toString().'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('description', 'like', $term);
            });
        }

        $sort = $request->string('sort')->toString();
        match ($sort) {
            'price-low' => $query->orderBy('price', 'asc'),
            'price-high' => $query->orderBy('price', 'desc'),
            'new-arrivals' => $query->orderByDesc('id'),
            'percentage-off' => $query->orderByDesc('percentage_off'),
            'featured' => $query->orderByDesc('rating')->orderByDesc('reviews_count'),
            default => $query->orderByDesc('id'),
        };

        $paginator = $query->paginate((int) $request->input('per_page', 15))->withQueryString();

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

    public function show(int $id)
    {
        $product = Product::query()
            ->with(['images' => fn ($q) => $q->orderBy('sort_order')])
            ->where('id', (int) $id)
            ->firstOrFail();

        return $this->success(ProductPresenter::detail($product), 'OK');
    }

    public function uploadImage(Request $request)
    {
        Log::info('Product image upload request received', [
            'has_image' => $request->hasFile('image'),
            'content_type' => $request->header('Content-Type'),
            'request_keys' => array_keys($request->all()),
        ]);

        if (! $request->hasFile('image')) {
            Log::warning('Product image upload failed: missing image file', [
                'request_keys' => array_keys($request->all()),
            ]);
            return $this->error('Image file is required. Use field name "image".', 422);
        }

        $request->validate(['image' => 'image|max:5120']);

        $file = $request->file('image');
        if (! $file || ! $file->isValid()) {
            Log::warning('Product image upload failed: invalid uploaded file', [
                'error' => $file?->getErrorMessage(),
            ]);
            return $this->error('Uploaded image is invalid.', 422);
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '') {
            Log::error('Product image upload failed: unable to read temp file path');
            return $this->error('Unable to process uploaded image.', 422);
        }

        try {
            $result = cloudinary()->uploadApi()->upload($realPath, [
                'folder' => 'guess/products',
            ]);

            Log::info('Cloudinary upload response', [
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cloudinary upload exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('Failed to upload image to Cloudinary.', 500);
        }

        $url = $result['secure_url'] ?? null;
        if (! is_string($url) || $url === '') {
            Log::error('Cloudinary upload missing secure_url', [
                'result' => $result,
            ]);
            return $this->error('Upload failed: Cloudinary did not return a URL.', 500);
        }

        return $this->success(['url' => $url], 'Uploaded');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedProductPayload($request, true);
        if (empty($validated['category_path']) || empty($validated['colors']) || empty($validated['sizes'])) {
            return $this->error('category_path, colors, and sizes are required', 422);
        }
        $slug = ! empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);
        if (Product::query()->where('slug', $slug)->exists()) {
            return $this->error('Slug already exists', 422);
        }

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'gender' => $validated['gender'],
            'price' => $validated['price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'percentage_off' => $validated['percentage_off'] ?? 0,
            'material' => $validated['material'] ?? null,
            'eco_info' => $validated['eco_info'] ?? null,
            'rating' => $validated['rating'] ?? 0,
            'reviews_count' => $validated['reviews_count'] ?? 0,
            'availability' => $validated['availability'] ?? 'In Stock',
            'brand' => $validated['brand'] ?? 'GUESS',
            'description' => $validated['description'] ?? null,
            'category_path' => $validated['category_path'],
            'tags' => $validated['tags'],
            'colors' => $validated['colors'],
            'sizes' => $validated['sizes'],
        ]);

        $this->syncImages($product, $validated['images_by_color'] ?? null);
        $product->load(['images' => fn ($q) => $q->orderBy('sort_order')]);

        return $this->success(ProductPresenter::detail($product), 'Created', 201);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::query()->findOrFail($id);
        $validated = $this->validatedProductPayload($request, false);

        if (isset($validated['slug']) && $validated['slug'] !== $product->slug) {
            if (Product::query()->where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                return $this->error('Slug already exists', 422);
            }
        }

        $keys = [
            'name', 'slug', 'gender', 'price', 'sale_price', 'percentage_off', 'material', 'eco_info',
            'rating', 'reviews_count', 'availability', 'brand', 'description', 'category_path', 'tags', 'colors', 'sizes',
        ];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $product->{$key} = $validated[$key];
        }

        $product->save();

        if (array_key_exists('images_by_color', $validated)) {
            $this->syncImages($product, $validated['images_by_color']);
        }

        $product->load(['images' => fn ($q) => $q->orderBy('sort_order')]);

        return $this->success(ProductPresenter::detail($product), 'Updated');
    }

    public function destroy(int $id)
    {
        $product = Product::query()->findOrFail($id);
        $product->delete();

        return $this->success(null, 'Deleted');
    }

    private function validatedProductPayload(Request $request, bool $creating): array
    {
        $rules = [
            'name' => ($creating ? 'required' : 'sometimes').'|string|max:255',
            'slug' => 'nullable|string|max:255',
            'gender' => ($creating ? 'required' : 'sometimes').'|in:women,men,unisex',
            'price' => ($creating ? 'required' : 'sometimes').'|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'percentage_off' => 'nullable|integer|min:0|max:100',
            'material' => 'nullable|string|max:500',
            'eco_info' => 'nullable|string|max:500',
            'rating' => 'nullable|numeric|min:0|max:5',
            'reviews_count' => 'nullable|integer|min:0',
            'availability' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_path' => ($creating ? 'required' : 'sometimes'),
            'tags' => ($creating ? 'required' : 'sometimes'),
            'colors' => ($creating ? 'required' : 'sometimes'),
            'sizes' => ($creating ? 'required' : 'sometimes'),
            'images_by_color' => 'nullable|array',
        ];

        $validated = $request->validate($rules);

        foreach (['category_path', 'tags', 'colors', 'sizes'] as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }
            $validated[$key] = $this->normalizeList($validated[$key]);
        }

        return $validated;
    }

    private function normalizeList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
        }
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }

        return [];
    }

    private function syncImages(Product $product, ?array $imagesByColor): void
    {
        $product->images()->delete();
        if (! $imagesByColor) {
            return;
        }
        foreach ($imagesByColor as $color => $urls) {
            if (! is_array($urls)) {
                continue;
            }
            foreach (array_values($urls) as $order => $url) {
                if (! is_string($url) || $url === '') {
                    continue;
                }
                ProductImage::create([
                    'product_id' => $product->id,
                    'color' => (string) $color,
                    'image_url' => $url,
                    'sort_order' => $order,
                ]);
            }
        }
    }
}
