<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class WithdrawalTransactionTest extends TestCase
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

    public function test_withdrawal_deducts_amount_and_fee_and_preserves_shamcash_number(): void
    {
        [$customer, $wallet] = $this->createUserWithWallet('customer', 1000);
        Sanctum::actingAs($customer);

        $response = $this->postJson('/api/withdraw', [
            'amount' => '100.00',
            'shamcash_number' => '0012345678',
        ]);

        $response->assertOk();
        $this->assertEqualsWithDelta(899.00, (float) $wallet->fresh()->balance, 0.001);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'status' => 'pending',
            'amount' => 100,
            'shamcash_number' => '0012345678',
        ]);
    }

    public function test_cancelled_withdrawal_refunds_saved_amount_and_fee(): void
    {
        [$customer, $customerWallet] = $this->createUserWithWallet('customer', 1000);
        Sanctum::actingAs($customer);
        $this->postJson('/api/withdraw', [
            'amount' => '100.00',
            'shamcash_number' => '0012345678',
        ])->assertOk();

        $transaction = Transaction::where('wallet_id', $customerWallet->id)->firstOrFail();
        [$admin] = $this->createUserWithWallet('admin', 0);
        Sanctum::actingAs($admin);

        $this->postJson("/api/handleWithdrawTransaction/{$transaction->id}", [
            'status' => 'cancelled',
        ])->assertOk();

        $this->assertEqualsWithDelta(1000.00, (float) $customerWallet->fresh()->balance, 0.001);
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
    }

    public function test_approved_withdrawal_completes_transaction_and_credits_fee_to_admin(): void
    {
        [$customer, $customerWallet] = $this->createUserWithWallet('customer', 1000);
        Sanctum::actingAs($customer);
        $this->postJson('/api/withdraw', [
            'amount' => '100.00',
            'shamcash_number' => '0012345678',
        ])->assertOk();

        $transaction = Transaction::where('wallet_id', $customerWallet->id)->firstOrFail();
        [$admin, $adminWallet] = $this->createUserWithWallet('admin', 0);
        Sanctum::actingAs($admin);

        $this->postJson("/api/handleWithdrawTransaction/{$transaction->id}", [
            'status' => 'approved',
        ])->assertOk();

        $this->assertEqualsWithDelta(899.00, (float) $customerWallet->fresh()->balance, 0.001);
        $this->assertEqualsWithDelta(1.00, (float) $adminWallet->fresh()->balance, 0.001);
        $this->assertSame('completed', $transaction->fresh()->status);
    }

    public function test_completed_transactions_can_be_filtered_without_validation_error(): void
    {
        [$customer, $wallet] = $this->createUserWithWallet('customer', 1000);
        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'status' => 'completed',
            'description' => 'Withdrawal request',
            'shamcash_number' => '0012345678',
            'amount' => 100,
        ]);
        Sanctum::actingAs($customer);

        $response = $this->getJson('/api/getMyTransactionByStatus?status=completed');

        $response->assertOk()
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('transactions.data.0.status', 'completed');
    }

    private function createUserWithWallet(string $role, float $balance): array
    {
        $user = User::create([
            'email' => uniqid($role, true).'@example.com',
            'password' => 'password',
            'role' => $role,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => $balance,
            'wallet_pin' => '1234',
        ]);

        return [$user, $wallet];
    }
}
