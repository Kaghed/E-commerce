<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
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
