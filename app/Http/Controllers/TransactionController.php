<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
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

        return response()->json('Transaction created, and now it is pending', 200);
    }
}
