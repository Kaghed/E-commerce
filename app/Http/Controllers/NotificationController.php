<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function AllNotification(): JsonResponse
    {
        $user = Auth::user();

        $notifications = Notification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function MarkAsRead(int $id): JsonResponse
    {
        $user = Auth::user();

        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        /*
         * قاعدة البيانات والموديل يستخدمان is_read،
         * لذلك يجب تحديث هذا الحقل وليس read_at.
         */
        if (!$notification->is_read) {
            $notification->is_read = true;
            $notification->save();
        }

        return response()->json([
            'message' => 'Notification marked as read',
            'data' => $notification->fresh(),
        ]);
    }

    public function deleteNotification(int $id): JsonResponse
    {
        $user = Auth::user();

        /*
         * نبحث ضمن إشعارات المستخدم نفسه مباشرة.
         * هذا يمنع المستخدم من حذف إشعار تابع لمستخدم آخر.
         */
        $notification = Notification::query()
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }
}