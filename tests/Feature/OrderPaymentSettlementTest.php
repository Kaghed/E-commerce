<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Report;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class OrderPaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(
            FirebaseNotificationService::class,
            function (MockInterface $mock) {
                $mock->shouldReceive('sendToUser')
                    ->zeroOrMoreTimes()
                    ->andReturn(['success' => true]);
            }
        );
    }

    public function test_buyer_confirmation_completes_order_and_original_payment(): void
    {
        $data = $this->createPendingOrder();
        Sanctum::actingAs($data['customer']);

        $this->postJson("/api/order/{$data['order']->id}/confirm")
            ->assertOk();

        $this->assertSame('complete', $data['order']->fresh()->status);
        $this->assertSame('completed', $data['payment']->fresh()->status);
        $this->assertEqualsWithDelta(450.00, (float) $data['sellerWallet']->fresh()->balance, 0.001);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $data['sellerWallet']->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 450,
        ]);
    }

    public function test_repeated_buyer_confirmation_does_not_credit_seller_twice(): void
    {
        $data = $this->createPendingOrder();
        Sanctum::actingAs($data['customer']);

        $this->postJson("/api/order/{$data['order']->id}/confirm")
            ->assertOk();
        $this->postJson("/api/order/{$data['order']->id}/confirm");

        $this->assertEqualsWithDelta(450.00, (float) $data['sellerWallet']->fresh()->balance, 0.001);
        $this->assertSame(1, Transaction::query()
            ->where('wallet_id', $data['sellerWallet']->id)
            ->where('type', 'deposit')
            ->count());
    }

    public function test_seller_rejection_completes_original_payment_before_refund(): void
    {
        $data = $this->createPendingOrder();
        Sanctum::actingAs($data['seller']);

        $this->deleteJson("/api/orders/{$data['order']->id}/reject")
            ->assertOk();

        $this->assertSame('completed', $data['payment']->fresh()->status);
        $this->assertEqualsWithDelta(1000.00, (float) $data['customerWallet']->fresh()->balance, 0.001);
        $this->assertDatabaseMissing('orders', ['id' => $data['order']->id]);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $data['customerWallet']->id,
            'type' => 'refund',
            'status' => 'completed',
            'amount' => 450,
        ]);
    }

    public function test_accepted_report_completes_original_payment_before_refund(): void
    {
        $data = $this->createPendingOrder();
        $admin = $this->createUser('admin');
        $report = Report::create([
            'reporter_id' => $data['customer']->id,
            'reportable_id' => $data['order']->id,
            'reportable_type' => Order::class,
            'description' => 'Product was not received.',
            'status' => 'pending',
        ]);
        Sanctum::actingAs($admin);

        $this->postJson("/api/order-reports/{$report->id}/accept")
            ->assertOk();

        $this->assertSame('completed', $data['payment']->fresh()->status);
        $this->assertSame('accepted', $report->fresh()->status);
        $this->assertEqualsWithDelta(1000.00, (float) $data['customerWallet']->fresh()->balance, 0.001);
        $this->assertDatabaseMissing('orders', ['id' => $data['order']->id]);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $data['customerWallet']->id,
            'type' => 'refund',
            'status' => 'completed',
            'amount' => 450,
        ]);
    }

    private function createPendingOrder(): array
    {
        $customer = $this->createUser('customer');
        $seller = $this->createUser('seller');
        $customerWallet = $this->createWallet($customer, 550);
        $sellerWallet = $this->createWallet($seller, 0);
        $category = Category::create(['name' => uniqid('category-', true)]);
        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Test product',
            'description' => 'Test description',
            'price' => 225,
            'quantity' => 8,
            'governorate' => 'Damascus',
            'is_active' => true,
        ]);
        $payment = Transaction::create([
            'wallet_id' => $customerWallet->id,
            'type' => 'payment',
            'status' => 'pending',
            'description' => 'Payment for product: Test product',
            'amount' => 450,
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'transaction_id' => $payment->id,
            'quantity' => 2,
            'total_price' => 450,
            'status' => 'pending',
        ]);

        return compact(
            'customer',
            'seller',
            'customerWallet',
            'sellerWallet',
            'product',
            'payment',
            'order',
        );
    }

    private function createUser(string $role): User
    {
        return User::create([
            'email' => uniqid($role, true).'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);
    }

    private function createWallet(User $user, float $balance): Wallet
    {
        return Wallet::create([
            'user_id' => $user->id,
            'balance' => $balance,
            'wallet_pin' => '1234',
        ]);
    }
}
