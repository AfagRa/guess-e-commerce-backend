<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasSingleColumnIndex('products', 'gender')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('gender', 'products_gender_index');
            });
        }

        if (! $this->hasSingleColumnIndex('products', 'slug')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('slug', 'products_slug_index');
            });
        }

        if (! $this->hasSingleColumnIndex('product_images', 'product_id')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->index('product_id', 'product_images_product_id_index');
            });
        }

        if (! $this->hasSingleColumnIndex('product_images', 'sort_order')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->index('sort_order', 'product_images_sort_order_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('products', 'products_gender_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_gender_index');
            });
        }

        if (Schema::hasIndex('products', 'products_slug_index')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_slug_index');
            });
        }

        if (Schema::hasIndex('product_images', 'product_images_product_id_index')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->dropIndex('product_images_product_id_index');
            });
        }

        if (Schema::hasIndex('product_images', 'product_images_sort_order_index')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->dropIndex('product_images_sort_order_index');
            });
        }
    }

    private function hasSingleColumnIndex(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if ($index['columns'] === [$column]) {
                return true;
            }
        }

        return false;
    }
};
