<?php

namespace App\Services\Auth;

use App\Http\Controllers\OtpController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use App\Mail\OtpMail;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Request as FacadesRequest;

class AuthService
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }
    public function register(RegisterRequest $request)
    {
        $otpRequest = new Request([
            'email' => $request->email
        ]);

        $otp = app(OtpController::class)->send($otpRequest);

        $profileImagePath = $request->file('profile_image')
            ->store('temp/profiles', 'public');

        $identityImagePath = null;

        if ($request->hasFile('identity_image')) {
            $identityImagePath = $request->file('identity_image')
                ->store('temp/identities', 'public');
        }

        $data = [
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,

            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'governorate' => $request->governorate,
            'date_of_birth' => $request->date_of_birth,

            'wallet_pin' => Hash::make($request->wallet_pin),

            'profile_image' => $profileImagePath,
            'identity_image' => $identityImagePath,
        ];

        Cache::put(
            'register_' . $request->email,
            [
                'otp' => $otp,
                'data' => $data
            ],
            now()->addMinutes(10)
        );

        Mail::to($request->email)->send(new OtpMail($otp));

        return true;
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
            'user'=> new UserResource($user),
            'profile' => new ProfileResource($user->profile),
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
