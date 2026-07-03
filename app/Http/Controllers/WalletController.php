<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WalletController extends Controller
{
    public function changePin(Request $request){

        $request->validate([
            'password' => 'required|string',
            'new_pin' => 'required|numeric|digits:4'
        ]);

        $user = Auth::user();

        if(!Hash::check($request->password, $user->password)){
            return response()->json('You have entered wrong password, Try again!',403);
        }

        $user->wallet->update([
            'wallet_pin' => Hash::make($request->new_pin)
        ]);

        return response()->json(['pin changed successfuly',$request->new_pin] , 201);

    }

    public function checkPin(Request $request){
        $request->validate([
            'wallet_pin' => 'required|numeric|digits:4'
        ]);

        $user = Auth::user();

        if(!Hash::check($request->wallet_pin , $user->wallet->wallet_pin)){
            return response()->json('You entered wrong pin' , 403);
        }

        return response()->json('Your pin is correct' , 200);

    }
}
