<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_rate_product_from_completed_order_once(): void
    {
        $seller = $this->createUser('seller');
        $customer = $this->createUser('customer');
        $product = $this->createProduct($seller, 'Rated product');
        $order = $this->createOrder($customer, $product, 'complete');
        Sanctum::actingAs($customer);

        $this->postJson('/api/rate-seller', [
            'order_id' => $order->id,
            'value' => 2,
        ])->assertOk();

        $this->assertDatabaseHas('ratings', [
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'value' => 2,
        ]);

        $this->postJson('/api/rate-seller', [
            'order_id' => $order->id,
            'value' => 5,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertSame(1, Rating::query()->count());
        $this->assertSame(2, (int) Rating::query()->firstOrFail()->value);
    }

    public function test_customer_cannot_rate_pending_or_another_customers_order(): void
    {
        $seller = $this->createUser('seller');
        $customer = $this->createUser('customer');
        $otherCustomer = $this->createUser('customer');
        $product = $this->createProduct($seller, 'Pending product');
        $pendingOrder = $this->createOrder($customer, $product, 'pending');
        $otherOrder = $this->createOrder($otherCustomer, $product, 'complete');
        Sanctum::actingAs($customer);

        $this->postJson('/api/rate-seller', [
            'order_id' => $pendingOrder->id,
            'value' => 4,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->postJson('/api/rate-seller', [
            'order_id' => $otherOrder->id,
            'value' => 4,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_customer_can_rate_different_products_from_same_seller(): void
    {
        $seller = $this->createUser('seller');
        $customer = $this->createUser('customer');
        $firstProduct = $this->createProduct($seller, 'First product');
        $secondProduct = $this->createProduct($seller, 'Second product');
        $firstOrder = $this->createOrder($customer, $firstProduct, 'complete');
        $secondOrder = $this->createOrder($customer, $secondProduct, 'complete');
        Sanctum::actingAs($customer);

        $this->postJson('/api/rate-seller', [
            'order_id' => $firstOrder->id,
            'value' => 2,
        ])->assertOk();
        $this->postJson('/api/rate-seller', [
            'order_id' => $secondOrder->id,
            'value' => 5,
        ])->assertOk();

        $this->assertDatabaseCount('ratings', 2);
    }

    public function test_customer_orders_response_marks_rated_products(): void
    {
        $seller = $this->createUser('seller');
        $customer = $this->createUser('customer');
        $product = $this->createProduct($seller, 'Rated order product');
        $order = $this->createOrder($customer, $product, 'complete');
        Rating::create([
            'customer_id' => $customer->id,
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'value' => 3,
        ]);
        Sanctum::actingAs($customer);

        $this->getJson('/api/ShowOrderByCustomer?status=complete')
            ->assertOk()
            ->assertJsonPath('0.id', $order->id)
            ->assertJsonPath('0.has_rating', true);
    }

    public function test_seller_rating_returns_most_frequent_value_instead_of_average(): void
    {
        $seller = $this->createUser('seller');
        $product = $this->createProduct($seller, 'Mode product');

        foreach ([2, 2, 2, 2, 2, 5, 5, 1] as $value) {
            $customer = $this->createUser('customer');
            Rating::create([
                'customer_id' => $customer->id,
                'seller_id' => $seller->id,
                'product_id' => $product->id,
                'value' => $value,
            ]);
        }

        Sanctum::actingAs($this->createUser('customer'));

        $this->getJson("/api/sellers/{$seller->id}/rating")
            ->assertOk()
            ->assertJsonPath('most_common_rating', 2)
            ->assertJsonPath('average_rating', 2)
            ->assertJsonPath('rating_count', 8);
    }

    private function createUser(string $role): User
    {
        return User::create([
            'email' => uniqid($role, true).'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function createProduct(User $seller, string $title): Product
    {
        $category = Category::firstOrCreate(['name' => 'Test category']);

        return Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => 'Test description',
            'price' => 100,
            'quantity' => 5,
            'governorate' => 'Damascus',
            'is_active' => true,
        ]);
    }

    private function createOrder(
        User $customer,
        Product $product,
        string $status,
    ): Order {
        return Order::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'total_price' => $product->price,
            'status' => $status,
        ]);
    }
}
