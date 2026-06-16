<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Illuminate\Support\minutes;

class AdminController extends Controller
{
    public function showUsers()
    {
        $users = User::paginate(10);

        return response()->json([
            'users' => UserResource::collection($users),

            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    public function blockUser(Request $request){

        $request->validate([
            'user_id' => 'required|integer',
            'ban_reason' => 'required|string|min:5|max:100',
        ]);

        $cur_user = Auth::user();
        if($cur_user ->id == $request->user_id ){
            return response()->json('You can\'t ban yourself', 403);
        }
            $user = User::where('id' , $request->user_id)->firstOrFail();

        $user->update([
            'ban_reason' => $request->ban_reason,
            'banned_until' => now()->addDays(3)
        ]);

        $user->currentAccessToken()->delete();
        return response()->json([
            'message' => 'User banned successfully',
            'banned_until' => $user->banned_until
        ], 200);

    }

    public function unBlockUser(int $user_id){

        $cur_user = Auth::user();
        if ($cur_user->id == $user_id) {
            return response()->json('This is an admin id', 403);
        }
        $user = User::where('id', $user_id)->firstOrFail();

        if(!$user->banned_until){
            return response()->json('This user is not banned', 403);
        }
        $user->update([
            'ban_reason' => null,
            'banned_until' => null
        ]);

        return response()->json([
            'message' => 'User Unbanned successfully',
        ], 200);
    }

    public function getBlockedUsers(){

        $users = User::whereNotNull('ban_reason')->paginate(10);


        return response()->json([
            'users' => UserResource::collection($users),

            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);

    }
}
