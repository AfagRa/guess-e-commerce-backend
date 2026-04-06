<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'gender',
        'price',
        'sale_price',
        'percentage_off',
        'material',
        'eco_info',
        'rating',
        'reviews_count',
        'availability',
        'brand',
        'description',
        'category_path',
        'tags',
        'colors',
        'sizes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'percentage_off' => 'integer',
            'rating' => 'decimal:2',
            'reviews_count' => 'integer',
            'category_path' => 'array',
            'tags' => 'array',
            'colors' => 'array',
            'sizes' => 'array',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function basketItems(): HasMany
    {
        return $this->hasMany(BasketItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
