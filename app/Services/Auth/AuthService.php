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
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response(['message' => 'Invalid login details'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [$token];
    }
}
