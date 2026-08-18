<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;


class TransactionController extends Controller
{
    public function __construct(
        private FirebaseNotificationService $notificationService,

    ) {}

    public function deposit(Request $request){

        $request->validate([
            'transfer_number'=>'required|string',
            'amount' => 'required|string'
        ]);

        $user = Auth::user();

        Transaction::create([
            'wallet_id' => $user->wallet->id,
            'type' => 'deposit',
            'status' => 'pending',
            'amount' => $request->amount,
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


        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'shamcash_number' => ['required', 'string', 'regex:/^\d+$/', 'max:50']
        ]);

        $user = Auth::user();
        $amount = round((float) $validated['amount'], 2);
        $tax = round($amount * 0.01, 2);
        $total = round($amount + $tax, 2);

        DB::transaction(function () use ($user, $validated, $amount, $total) {
            $wallet = Wallet::where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $wallet->balance < $total) {
                throw new HttpResponseException(
                    response()->json('You don\'t have enough money', 403)
                );
            }

            $wallet->balance = round((float) $wallet->balance - $total, 2);
            $wallet->save();

            Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'withdraw',
                'status' => 'pending',
                'description' => 'Withdrawal request',
                'shamcash_number' => $validated['shamcash_number'],
                'amount' => $amount
            ]);
        });

        $this->notificationService->sendToUser(
                userId: 1,
                title: 'NewWithdrawTransaction',
                body: "there is a new withdraw request for you ",
            );

        return response()->json('Your transaction created and now it is pending', 200);

    }


    public function getMyTransactionByStatus(Request $request){

        $request->validate([
            'status' => 'required|string|in:pending,completed'
        ]);

        $user = Auth::user();

        $transactions = Transaction::where('status', $request->status)
            ->where('wallet_id', $user->wallet->id)
            ->paginate(10);


        return response()->json([

            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
            'transactions' => $transactions
        ]);

    }
}
