<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $code = rand(100000, 999999);

        Otp::create([
            'email' => $request->email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5)
        ]);

        Mail::to($request->email)->send(new OtpMail($code));

        return response()->json([
            $request->email,
            'message' => 'OTP sent to email'
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $otp = Otp::where('email', $request->email)
            ->latest()
            ->first();

        if (!$otp) {
            return response()->json(['message' => 'OTP not found'], 404);
        }

        if ($otp->expires_at < now()) {
            return response()->json(['message' => 'OTP expired'], 400);
        }

        if (!Hash::check($request->code, $otp->code)) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        $otp->delete();

        // login / register
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['password' => bcrypt(rand(10000000, 99999999))]
        );

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'message' => 'Authenticated',
            'token' => $token,
            'user' => $user
        ]);
    }
}
