<?php

namespace App\Services;


use App\Models\Transaction;
use App\Models\Wallet;


class WalletService
{
    public function completePendingPayment(?int $transactionId): ?Transaction
    {
        if ($transactionId === null) {
            return null;
        }

        $transaction = Transaction::query()
            ->whereKey($transactionId)
            ->lockForUpdate()
            ->first();

        if (!$transaction || $transaction->type !== 'payment') {
            return $transaction;
        }

        if ($transaction->status === 'pending') {
            $transaction->update(['status' => 'completed']);
        }

        return $transaction->fresh();
    }

    
    public function debit(Wallet $wallet, float $amount,  $type, string $description){
     
       $wallet->decrement('balance', $amount);

        return Transaction::create([
            'wallet_id'   => $wallet->id,
            'type'        => $type,
            'description' => $description,
            'amount'      => $amount,
            'status'      => 'pending',
        ]);
    }

    

    public function credit(Wallet $wallet, float $amount,$type, string $description){ 

        $wallet->increment('balance', $amount);

        return Transaction::create([
            'wallet_id'   => $wallet->id,
            'type'        => $type,
            'description' => $description,
            'amount'      => $amount,
            'status'      => 'completed',
        ]);
    }
}
