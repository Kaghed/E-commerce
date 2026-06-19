<?php


namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminService{

public function blockUser(Request $request){
        $request->validate([
            'user_id' => 'required|integer',
            'ban_reason' => 'required|string|min:5|max:100',
        ]);

        $cur_user = Auth::user();
        if ($cur_user->id == $request->user_id) {
            return ['message'=>'you can\'t ban yourself',
                    'ban_reason'=>null,
                    'banned_until' => null,
                    'status'=>403
                    ];

        }
        $user = User::where('id', $request->user_id)->firstOrFail();

        $bannedUntil = now()->addDays(3);

        $user->update([
            'ban_reason' => $request->ban_reason,
            'banned_until' => $bannedUntil
        ]);

        $user->tokens()->delete();

        return [
            'message' => 'User banned successfully',
            'ban_reason'=> $request->ban_reason,
            'banned_until' =>  $bannedUntil,
            'status' => 200
        ];

    }


}