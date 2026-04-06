<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Sale', 'slug' => 'sale', 'sort_order' => 0, 'children' => []],
            ['name' => 'Women', 'slug' => 'women', 'sort_order' => 1, 'children' => [
                ['name' => 'Apparel', 'slug' => 'apparel', 'sort_order' => 0],
                ['name' => 'Accessories', 'slug' => 'accessories', 'sort_order' => 1],
                ['name' => 'Featured Shops', 'slug' => 'featured-shops', 'sort_order' => 2],
            ]],
            ['name' => 'Handbags', 'slug' => 'handbags', 'sort_order' => 2, 'children' => [
                ['name' => 'Shop by Style', 'slug' => 'shop-by-style', 'sort_order' => 0],
                ['name' => 'Collections', 'slug' => 'collections', 'sort_order' => 1],
            ]],
            ['name' => 'Shoes', 'slug' => 'shoes', 'sort_order' => 3, 'children' => [
                ['name' => 'Shop by Style', 'slug' => 'shoes-shop-by-style', 'sort_order' => 0],
            ]],
            ['name' => 'Men', 'slug' => 'men', 'sort_order' => 4, 'children' => [
                ['name' => 'Apparel', 'slug' => 'men-apparel', 'sort_order' => 0],
                ['name' => 'Accessories', 'slug' => 'men-accessories', 'sort_order' => 1],
            ]],
            ['name' => 'Marciano', 'slug' => 'marciano', 'sort_order' => 5, 'children' => [
                ['name' => 'Women', 'slug' => 'marciano-women', 'sort_order' => 0],
                ['name' => 'Men', 'slug' => 'marciano-men', 'sort_order' => 1],
            ]],
            ['name' => 'Guess Jeans', 'slug' => 'guess-jeans', 'sort_order' => 6, 'children' => [
                ['name' => 'Women', 'slug' => 'guess-jeans-women', 'sort_order' => 0],
                ['name' => 'Men', 'slug' => 'guess-jeans-men', 'sort_order' => 1],
                ['name' => 'Discover', 'slug' => 'discover', 'sort_order' => 2],
            ]],
        ];

        foreach ($data as $order => $row) {
            $parent = Category::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'parent_id' => null,
                    'sort_order' => $row['sort_order'],
                ]
            );

            foreach ($row['children'] as $childOrder => $child) {
                Category::updateOrCreate(
                    ['slug' => $child['slug']],
                    [
                        'name' => $child['name'],
                        'parent_id' => $parent->id,
                        'sort_order' => $child['sort_order'],
                    ]
                );
            }
        }
    }
}