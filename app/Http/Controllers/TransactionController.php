<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseNotificationService;


class TransactionController extends Controller
{
    public function __construct(
        private FirebaseNotificationService $notificationService,

    ) {}

    public function deposit(Request $request){

        $request->validate([
            'transfer_number'=>'required|string'
        ]);

        $user = Auth::user();

        Transaction::create([
            'wallet_id' => $user->wallet->id,
            'type' => 'deposit',
            'status' => 'pending',
            'description'=>'transfer number = ' . $request->transfer_number,
        ]);

      $this->notificationService->sendToUser(
                userId: 1,
                title: 'NewDepositTransaction',
                body: "there is a new deposit request for you ",
            );

        return response()->json('Transaction created, and now it is pending', 200);
    }


    public function withdraw(Request $request){


        $request->validate([
            'amount' => 'required|numeric|min:1',
            'shamcash_number'=>'required|numeric'
        ]);

        $user = Auth::user();
        $tax = $request->amount * 0.01;

        if($request->amount + $tax > $user->wallet->balance){
            return response()->json('You don\'t have enough money', 403);
        }

        $wallet = $user->wallet;
        $wallet->balance -= $request->amount;
        $wallet->save();

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'withdraw',
            'status' => 'pending',
            'description' => 'with draw my money',
            'amount' => $request->amount
        ]);

        $this->notificationService->sendToUser(
                userId: 1,
                title: 'NewWithdrawTransaction',
                body: "there is a new withdraw request for you ",
            );

        return response()->json('Your transaction created and now it is pending', 200);

    }
}
