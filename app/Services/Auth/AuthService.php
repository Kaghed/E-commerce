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
                'username' => $request->user_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role
            ]);

            $profileImagePath = $request->file('profile_image_url')
                ->store('profiles', 'public');

            $profile = $user->profile()->create([
                'full_name' => trim($request->full_name),
                'date_of_birth' => $request->date_of_birth,
                'profile_image_url' => $profileImagePath,
            ]);

            return [
                'user' => $user,
                'profile' => $profile
            ];
        });
    }
    public function login (LoginRequest $request){

        $user = User::where('email', $request->email)->firstOrFail();

            if (!Hash::check($request->password, $user->password))
                return['message' => 'Wrong password try again!' ,'token' =>null];
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
