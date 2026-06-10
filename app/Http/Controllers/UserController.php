<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Otp;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;

class UserController extends Controller
{
    public function __construct(
        protected AuthService $authService,
           protected OtpService $otpService
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
            'token' => $data['token']
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
}
