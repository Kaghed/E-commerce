<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\AuthService;

class UserController extends Controller
{
    public function __construct(
        protected AuthService $authService
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

        $token = $this->authService->login($request);

        return response()->json([
            'message' => 'Login successfully',
            'token' => $token
        ], 200);
    }

    function logout(Request $request)
    {

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successfully'], 200);
    }
}
