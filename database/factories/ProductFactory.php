<?php

namespace Database\Factories;

use App\Enums\Governorate;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
             
            'seller_id' => 3,
            'category_id' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'quantity' => $this->faker->numberBetween(1, 20),
            'governorate' => $this->faker->randomElement(
                array_column(Governorate::cases(), 'value')
            ),
            'product_image_url' => $this->faker->imageUrl(640, 480, 'products'),
            'product_url' => $this->faker->url(),
            'is_active' => true

        ];
    }
}
