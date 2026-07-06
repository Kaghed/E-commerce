<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{

    public function __construct(
        protected OtpService $otpService
    ) {
    }
    public function send(Request $request)
    {

        return response()->json(
            $this->otpService->send($request)
        );
    }

    public function verify(Request $request)
    {
        $data = $this->otpService->verify($request);
        // $status= 404;
        // if($data['status']==1)
        // $status = 200;
        return response()->json([
            'message' => $data['message']
        ], $data['status']);
    }
}
