<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('gender', ['women', 'men', 'unisex']);
            $table->decimal('price', 8, 2);
            $table->decimal('sale_price', 8, 2)->nullable();
            $table->unsignedInteger('percentage_off')->default(0);
            $table->string('material')->nullable();
            $table->string('eco_info')->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('availability')->default('In Stock');
            $table->string('brand')->default('GUESS');
            $table->text('description')->nullable();
            $table->json('category_path');
            $table->json('tags');
            $table->json('colors');
            $table->json('sizes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
