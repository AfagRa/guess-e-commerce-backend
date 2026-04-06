<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // \Database\Seeders\AdminSeeder::class,
            // \Database\Seeders\CategorySeeder::class,
            // \Database\Seeders\ProductSeeder::class,
            AdminSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
