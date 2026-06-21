<?php
namespace App\Services;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage; 
use Kreait\Firebase\Messaging\Notification;
use App\Models\DeviceToken;
use App\Models\User;
use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(base_path(config('services.firebase.credentials')));
        $this->messaging = $factory->createMessaging();
    }

    public function sendToUser(int $userId, string $title , string $body)
    { 
     
       try {

         $tokens = DeviceToken::where('user_id', '=', $userId , 'and')->pluck('token')->toArray();
          if (empty($tokens)) {
            Log::warning("No device tokens found for user ID: {$userId}");
            return [
                'success' => false,
                'message' => 'No device tokens found for the user.'
            ];
        }
            $notification = NotificationModel::create([
                'user_id' => $userId,
                'title' => $title,
                'body' => $body,
                'is_read' => false,
            ]);
            log::info("Notification created in database for user ID: {$userId} with title: {$title}");
                    
            $firebasenotification = Notification::create($title, $body);
            $message = CloudMessage::new()->withNotification($firebasenotification)->withData([
                'notification_id' => (string) $notification->id,
                'user_id' => (string) $userId,
            ]);

            foreach ($tokens as $token) {
                try {
                  
                      $this->messaging->send($message->toToken($token));  
                  
        
                    log::info("Notification sent to token: {$token} for user ID: {$userId}");
                } 
                catch (\Exception $e) 
                {
                    Log::error("Failed to send notification to token: {$token} for user ID: {$userId}. Error: " . $e->getMessage());
                }
            }
    
            return [
                'success' => true,
                'data' => $notification,
                'message' => 'Notification sent successfully.'

            ];
    }catch (\Exception $e) {
        log::error("Failed to send notification to user ID: {$userId}. Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to send notification. Please try again later.'
        ];
    }
    }
}