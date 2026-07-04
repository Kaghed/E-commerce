<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
use App\Models\Otp;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function __construct(
        protected AuthService $authService,
           protected OtpService $otpService ,
           protected FirebaseNotificationService $firebase
    ) {}

    function register(RegisterRequest $request)
    {

        $data = $this->authService->register($request);

        return response()->json([
            'message' => 'User registered successfully',
        $data], 201);
    }

    public function verifyRegister(Request $request)
    {
        $cached = Cache::get('register_' . $request->email);

        if (!$cached) {
            return response()->json(['message' => 'Expired'], 400);
        }
        $otpRequest = new Request([
            'email' => $request->email,
            'otp'=> $request->otp
        ]);
        $result = $this->otpService->verify($otpRequest);

        if ($result['status'] != 200) {
            return response()->json([
                'message' => $result['message']
            ], $result['status']);
        }

        $data = $cached['data'];

        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role']
        ]);

        $profile = $user->profile()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'governorate' => $data['governorate'],
            'date_of_birth' => $data['date_of_birth'],
            'profile_image_url' => $data['profile_image'],
            'identity_image_url' => $data['identity_image']
        ]);

        $wallet = $user->wallet()->create([
            'balance' => 0,
            'wallet_pin' => $data['wallet_pin']
        ]);

        Cache::forget('register_' . $request->email);

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Account created',
            new UserResource($user),
            new ProfileResource($profile),
            'token' => $token
        ]);
    }
    function login(LoginRequest $request)
    {

        $data = $this->authService->login($request);

        if($data['token'] == null){
            return response()->json([
                'message' => $data['message'],
            ], 404);
        }
        return response()->json([
            'message' => 'Login successfully',
            'token' => $data['token'],
            'user' => $data['user'],
             'profile' => $data['profile'],

        ], 200);
    }

    function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successfully'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $data = $this->authService->forgotPassword($request);

            $status = $data['status'];
        return response()->json([
            'message' => $data['message']
        ], $status);
    }


// test
    public function sendNotification(Request $request){

      $user = FacadesAuth::user();
        $request->validate([
            'title'=>'required|string',
            'body'=>'required|string',

        ]);

       $this->firebase->sendToUser($user->id, $request->title, $request->body);

        return response()->json([
            'message'=>'Notification sent successfully.'
        ]);
}

}
