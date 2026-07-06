<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function AllNotification(){
                
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)
        ->latest()
        ->paginate(20);
        return response()->json($notifications);
       }

    public function MarkAsRead($id){
        $user = Auth::user();

       $notification = Notification::where('user_id', $user->id)
        ->where('id', $id)
        ->firstOrFail();

        $notification->update(['read_at' => true]);
        return response()->json(['message' => 'Notification marked as read']);
       }   

       public function deleteNotification($id)
    {
        $notification = Notification::findOrFail($id);
        $user = Auth::user();
        if ($notification->user_id !== $user->id) {
            return response()->json(['message'=>'Unauthorized'],403);
        }
        $notification->delete();

          return response()->json([
            'message' => 'notification deleted successfully'
        ], 200);
    }


}
