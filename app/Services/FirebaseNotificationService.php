<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory())->withServiceAccount(base_path(config('services.firebase.credentials')));

        $this->messaging = $factory->createMessaging();
    }

    public function sendToUser(
        int $userId,
        string $title,
        string $body
    ): array {
        try {
           
            $notification = NotificationModel::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'is_read' => false,
            ]);

            Log::info(
                "Notification created in database for user ID: {$userId} "
                . "with title: {$title}"
            );

            $tokens = DeviceToken::query()
                ->where('user_id', $userId)
                ->pluck('token')
                ->filter(
                    static fn ($token) =>
                        is_string($token) && trim($token) !== ''
                )
                ->unique()
                ->values()
                ->all();

            
                
            if (empty($tokens)) {
                Log::warning(
                    "No device tokens found for user ID: {$userId}. "
                    . "The notification remains available inside the app."
                );

                return [
                    'success' => true,
                    'push_sent' => false,
                    'sent_count' => 0,
                    'failed_count' => 0,
                    'data' => $notification,
                    'message' =>
                        'Notification saved successfully, but no device token was found.',
                ];
            }

            $firebaseNotification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($firebaseNotification)
                ->withData([
                    'notification_id' => (string) $notification->id,
                    'user_id' => (string) $userId,
                    'type' => 'database_notification',
                ]);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($tokens as $token) {
                try {
                    $this->messaging->send($message->toToken($token));
                    $sentCount++;

                    Log::info(
                        "Notification sent to token for user ID: {$userId}"
                    );
                } catch (\Throwable $exception) {
                    $failedCount++;

                    Log::error(
                        "Failed to send notification to a token for user ID: "
                        . "{$userId}. Error: {$exception->getMessage()}"
                    );
                }
            }

            return [
                'success' => true,
                'push_sent' => $sentCount > 0,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'data' => $notification,
                'message' => $sentCount > 0
                    ? 'Notification saved and sent successfully.'
                    : 'Notification saved, but push delivery failed.',
            ];
        } catch (\Throwable $exception) {
            Log::error(
                "Failed to create or send notification for user ID: {$userId}. "
                . "Error: {$exception->getMessage()}"
            );

            return [
                'success' => false,
                'push_sent' => false,
                'message' =>
                    'Failed to create the notification. Please try again later.',
            ];
        }
    }
}