<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Product::insert([
            [
                'name'        => 'Combo Popcorn + Soda',
                'type'        => 'combo',
                'price'       => 50000,
                'stock'       => 100,
                'image_url'   => 'products/combo1.jpg',
                'description' => '1 Large popcorn + 1 Large drink',
                'status'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Soft Drink',
                'type'        => 'drink',
                'price'       => 20000,
                'stock'       => 200,
                'image_url'   => 'products/drink.jpg',
                'description' => 'Coca-Cola 330ml',
                'status'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'Buttered Popcorn',
                'type'        => 'food',
                'price'       => 35000,
                'stock'       => 150,
                'image_url'   => 'products/popcorn.jpg',
                'description' => 'Medium size buttered popcorn',
                'status'      => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
