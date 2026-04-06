<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/products.json');
        if (! File::exists($path)) {
            return;
        }

        $json = File::get($path);
        $products = json_decode($json, true);
        if (! is_array($products)) {
            return;
        }

        foreach ($products as $row) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'gender' => $row['gender'] ?? 'unisex',
                    'price' => $row['price'] ?? 0,
                    'sale_price' => $row['salePrice'] ?? null,
                    'percentage_off' => $row['percentageOff'] ?? 0,
                    'material' => $row['material'] ?? null,
                    'eco_info' => $row['ecoInfo'] ?? null,
                    'rating' => $row['rating'] ?? 0,
                    'reviews_count' => $row['reviewsCount'] ?? 0,
                    'availability' => $row['availability'] ?? 'In Stock',
                    'brand' => $row['brand'] ?? 'GUESS',
                    'description' => $row['description'] ?? null,
                    'category_path' => $row['categoryPath'] ?? [],
                    'tags' => $row['tags'] ?? [],
                    'colors' => $row['colors'] ?? [],
                    'sizes' => $row['sizes'] ?? [],
                ]
            );

            $product->images()->delete();
            $imagesByColor = $row['imagesByColor'] ?? [];
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
}
