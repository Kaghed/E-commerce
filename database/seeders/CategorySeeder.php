<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Electronics', 'Clothing', 'Student_Supplies','Shoes','Books','Furniture','Houseware','cosmetics','Sports_Equipment','Toys_Games'];
        foreach ($categories as $category) {
            Category::create(['name' => $category]);
        }
    }
}
