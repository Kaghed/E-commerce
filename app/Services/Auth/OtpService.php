<?php

namespace App\Services\Auth;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Mail\OtpMail;
use App\Models\Otp;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function send(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $otp = rand(100000, 999999);

        Otp::create([
            'email' => $request->email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(5)
        ]);

        Mail::to($request->email)->send(new OtpMail($otp));

        return [
            'email' => $request->email,
            'message' => 'OTP sent to email'
        ];
    }

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $otp = Otp::where('email', $request->email)
            ->latest()
            ->first();

        if (!$otp) {
            return [
                'status' => 404,
                'message' => 'OTP not found'
            ];
        }

        if ($otp->expires_at < now()) {
            return [
                'status' => 400,
                'message' => 'OTP expired'
            ];
        }

        if (!Hash::check($request->otp, $otp->otp)) {
            return [
                'status' => 404,
                'message' => 'Invalid OTP'
            ];
        }

        $otp->delete();

        return [
            'status' => 200,
            'message' => 'OTP verified successfully'
        ];
    }

}
