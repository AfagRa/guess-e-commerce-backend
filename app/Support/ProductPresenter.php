<?php

namespace App\Support;

use App\Models\Product;

class ProductPresenter
{
    public static function summary(Product $product): array
    {
        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'gender' => $product->gender,
            'categoryPath' => $product->category_path,
            'price' => (float) $product->price,
            'salePrice' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'percentageOff' => (int) $product->percentage_off,
            'colors' => $product->colors,
            'sizes' => $product->sizes,
            'tags' => $product->tags,
            'rating' => (float) $product->rating,
            'reviewsCount' => (int) $product->reviews_count,
            'availability' => $product->availability,
            'brand' => $product->brand,
            'imageUrl' => $product->images->sortBy('sort_order')->first()?->image_url,
            'imagesByColor' => $product->images->sortBy('sort_order')->groupBy('color')->map(fn ($imgs) => $imgs->pluck('image_url')->values()->all())->toArray(),
        ];
    }

    public static function detail(Product $product): array
    {
        $imagesByColor = [];
        if ($product->relationLoaded('images')) {
            $grouped = $product->images->sortBy('sort_order')->groupBy('color');
            foreach ($grouped as $color => $rows) {
                $imagesByColor[$color] = $rows->values()->pluck('image_url')->all();
            }
        } else {
            foreach ($product->images()->orderBy('sort_order')->get()->groupBy('color') as $color => $rows) {
                $imagesByColor[$color] = $rows->pluck('image_url')->values()->all();
            }
        }

        return [
            'id' => (string) $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'gender' => $product->gender,
            'categoryPath' => $product->category_path,
            'price' => (float) $product->price,
            'salePrice' => $product->sale_price !== null ? (float) $product->sale_price : null,
            'percentageOff' => (int) $product->percentage_off,
            'colors' => $product->colors,
            'sizes' => $product->sizes,
            'tags' => $product->tags,
            'material' => $product->material,
            'ecoInfo' => $product->eco_info,
            'rating' => (float) $product->rating,
            'reviewsCount' => (int) $product->reviews_count,
            'availability' => $product->availability,
            'brand' => $product->brand,
            'description' => $product->description,
            'imagesByColor' => $imagesByColor,
        ];
    }
}
