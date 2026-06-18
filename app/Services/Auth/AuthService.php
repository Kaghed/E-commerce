<?php

namespace App\Services\Auth;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }
    public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {

            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role
            ]);

            $profileImagePath = $request->file('profile_image_url')
                ->store('profiles', 'public');

            $profile = $user->profile()->create([
                'first_name'=> $request->first_name,
                'last_name'=> $request->last_name,
                'governorate'=>$request->governorate,
                'date_of_birth' => $request->date_of_birth,
                'profile_image_url' => $profileImagePath,
            ]);

           $wallet =  $user->wallet()->create([
                'user_id'=> $user->id,
                'balance' => 0.0,
                'wallet_pin' => Hash::make($request->wallet_pin)
            ]);

            return [
                'user' => $user,
                'profile' => $profile,
                'wallet_balance' => $wallet->balance
            ];
        });
    }
    public function login (LoginRequest $request){

        $user = User::where('email', $request->email)->firstOrFail();

            if (!Hash::check($request->password, $user->password))
                return['message' => 'Wrong password try again!' ,'token' =>null];

                if($user->banned_until && $user->banned_until > now()){
            return ['message' =>
             'Sorry you are banned' ,
            $user->banned_until,
                $user->ban_reason
            , 'token' => null];

        }

            $user->ban_reason = null;
            $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message'=>'Login successfully',
         'token'=>   $token];

}

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required',
            'password' => 'required|min:6|confirmed'
        ]);

        $otpData = $this->otpService->verify(
            $request
        );

        if ($otpData['status']!=200) {

            return [
                'message' => $otpData['message'],
                'status' => $otpData['status']
            ];
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return [
                'message' => 'User not found',
                'status' => 404
            ];
        }

        $user->update([
            'password' => Hash::make($request->password)

        ]);


        return [
            'message' => 'Password reset successfully',
            'status' => 200
        ];
    }

}
