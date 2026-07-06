<?php

namespace App\Services;


use App\Models\Transaction;
use App\Models\Wallet;


class WalletService
{
    
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
