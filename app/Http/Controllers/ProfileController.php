<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{


    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed'
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'message' => 'Wrong password, try again!'
            ], 400);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'message' => 'This is already your password'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }
    
    public function update(UpdateProfileRequest $request)
    {
        $profile = Auth::user()->profile;

        $data = $request->validated();

        if ($request->hasFile('profile_image')) {

            $data['profile_image_url'] = $request
                ->file('profile_image')
                ->store('profiles', 'public');

            unset($data['profile_image']);
        }

        $profile->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $profile->fresh(),
            'profile_image_url' => asset(
                'storage/' . $profile->fresh()->profile_image_url
            ),
        ]);
    }

}
