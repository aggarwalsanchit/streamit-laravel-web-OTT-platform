<?php

namespace App\Notifications;

use App\Broadcasting\CustomWebhook;

use App\Mail\MailMailableSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\NotificationTemplate\Models\NotificationTemplate;
use Spatie\WebhookServer\WebhookCall;
use App\Broadcasting\FcmChannel;
use Modules\NotificationTemplate\Models\NotificationTemplateContentMapping;
use Google\Client as Google_Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


class CommonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $type;

    public $data;

    public $subject;
    
    public $email_subject;

    public $notification;

    public $notification_message;

    public $notification_link;

    public $appData;

    public $custom_webhook;

    /**
     * Create a new notification instance.
     */

    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;

        $userType = $data['user_type'];

        $this->notification = NotificationTemplate::where('type', $this->type)
            ->with('defaultNotificationTemplateMap')
            ->first();
        $notify_data = NotificationTemplateContentMapping::where('template_id', $this->notification->id)->get();
        $templateData = $notify_data->where('user_type', $userType)->first();
        $this->template_data = $templateData;
        $templateDetail = $templateData->notification_template_detail ?? null;
        $fullTemplateDetail = $templateData->template_detail ?? null;
        $notification_subject = $templateData->notification_subject ?? 'None';
        $email_subject = $templateData->subject ?? 'None';

        foreach ($this->data as $key => $value) {
            // Ensure days/days_remaining are integers for proper display
            if (in_array($key, ['days', 'days_remaining'])) {
                $value = (int) ceil((float) $value);
                $this->data[$key] = $value;
            }

            $valueStr = is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
            
            // Replace in Notification Body
            $templateDetail = str_replace(
                ['[[ ' . $key . ' ]]', '[[' . $key . ']]'],
                $valueStr,
                $templateDetail
            );

            // Replace in Email Body (Full Template Detail)
            if ($fullTemplateDetail) {
                $fullTemplateDetail = str_replace(
                    ['[[ ' . $key . ' ]]', '[[' . $key . ']]'],
                    $valueStr,
                    $fullTemplateDetail
                );
            }

            // Replace in Notification Subject
            $notification_subject = str_replace(
                ['[[ ' . $key . ' ]]', '[[' . $key . ']]'],
                $valueStr,
                $notification_subject
            );

            // Replace in Email Subject
            $email_subject = str_replace(
                ['[[ ' . $key . ' ]]', '[[' . $key . ']]'],
                $valueStr,
                $email_subject
            );
        }

        $this->data['type'] = $notification_subject;
        $this->subject = $notification_subject;
        $this->email_subject = $email_subject;
        
        // Store processed email content for toMail
        $this->template_data = $fullTemplateDetail;

        // Use notification_template_detail for push/in-app display
        // Fallback to email template only if notification template is empty
        $finalNotificationDetail = !empty($templateDetail) ? $templateDetail : $fullTemplateDetail;
        
        // Improve HTML-to-text conversion by adding spaces after tags before stripping
        // This ensures the plain-text notification has proper spacing
        $spacedMessage = str_replace('>', '> ', $finalNotificationDetail ?? '');
        $this->notification_message = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($spacedMessage ?? __('messages.default_notification_body')), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $this->data['message'] = $this->notification_message;
        $this->appData = $this->notification->channels;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $notificationSettings = $this->appData;
        $notification_settings = [];
        
        if (isset($notificationSettings) && is_array($notificationSettings)) {
            foreach ($notificationSettings as $key => $notification) {
                if ($notification) {
                    switch ($key) {
                        case 'PUSH_NOTIFICATION':
                            array_push($notification_settings, 'database');
                            array_push($notification_settings, FcmChannel::class);
                            break;

                        case 'IS_CUSTOM_WEBHOOK':
                            array_push($notification_settings, CustomWebhook::class);
                            break;

                        case 'IS_MAIL':
                            array_push($notification_settings, 'mail');
                            break;
                    }
                }
            }
        }

        return $notification_settings;
    }


    /**
     * Get mail notification
     *
     * @param  mixed  $notifiable
     * @return MailMailableSend
     */
    public function toMail($notifiable)
    {
        $email = '';

        if (isset($notifiable->email)) {
            $email = $notifiable->email;
        } else {
            $email = $notifiable->routes['mail'];
        }



        return (new MailMailableSend($this->notification, $this->data, $this->type, $this->template_data))->to($email)
            ->bcc(isset($this->notification->bcc) ? json_decode($this->notification->bcc) : [])
            ->cc(isset($this->notification->cc) ? json_decode($this->notification->cc) : [])
            ->subject($this->email_subject);
    }

    public function toWebhook($notifiable)
    {
        $key = setting('custom_webhook_content_key');
        $url = setting('custom_webhook_url');
        $secrate_key = setting('app_name');
        $msg = 'Subject: ' . $this->subject . "\nDescription: " . strip_tags($this->notification_message) . "\n" . 'Link: ' . $this->notification_link;

        return WebhookCall::create()
            ->url($url)
            ->payload([$key => $msg])
            ->useSecret($secrate_key)
            ->dispatch();
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'subject' => $this->subject,
            'message' => $this->notification_message,
            'data' => $this->data,
        ];
    }

    public function toFcm($notifiable)
    {
        
        $msg = isset($this->data['message']) ? $this->data['message'] : '';
        if (!isset($msg) && $msg == '' ) {
            $msg =  $this->subject;
        }
        $type = 'streamit';
        if (isset($this->notification->notification_subject) && $this->notification->notification_subject !== '') {
            $type = $this->notification->notification_subject;
        }
        $heading = $this->subject;
        $additionalData = json_encode($this->data);

        // Get thumbnail image based on notification type (handle both nested and top-level data)
        $innerData = $this->data['data'] ?? $this->data;
        $notificationType = $innerData['notification_type'] ?? $this->type;
        $thumbnailImage = $innerData['posterimage'] ?? $this->data['posterimage'] ?? null;

        if (!$thumbnailImage || !filter_var($thumbnailImage, FILTER_VALIDATE_URL)) {
            switch ($notificationType) {
                case 'movie_add':
                    $thumbnailImage = getThumbnail($innerData['movie_name'] ?? $innerData['name'] ?? null, 'movie');
                    break;
                case 'episode_add':
                    $thumbnailImage = getThumbnail($innerData['episode_name'] ?? $innerData['name'] ?? null, 'episode');
                    break;
                case 'season_add':
                    $thumbnailImage = getThumbnail($innerData['season_name'] ?? $innerData['name'] ?? null, 'season');
                    break;
                case 'tv_show_add':
                    $thumbnailImage = getThumbnail($innerData['tvshow_name'] ?? $innerData['name'] ?? null, 'tv_show');
                    break;
                case 'purchase_video':
                case 'rent_video':
                case 'one_time_purchase_content':
                case 'rental_content':
                    $contentType = $innerData['content_type'] ?? 'movie';
                    $thumbnailImage = getThumbnail($innerData['name'] ?? null, $contentType);
                    break;
                case 'new_subscription':
                case 'cancle_subscription':
                case 'expiry_plan':
                case 'subscription_expiry_reminder':
                    $thumbnailImage = url('default-image/Default-Subscription-Image.png');
                    break;
                case 'upcoming':
                    $contentType = $innerData['content_type'] ?? 'movie';
                    $thumbnailImage = getThumbnail($innerData['name'] ?? null, $contentType);
                    break;
                case 'continue_watch':
                    $contentType = $innerData['content_type'] ?? 'movie';
                    $thumbnailImage = getThumbnail($innerData['name'] ?? null, $contentType);
                    break;
            }
        }

        // For push notifications, we ONLY want to show an image if it's a specific content poster.
        // If it's a default/generic image (like Default-Subscription-Image.png or Default-Image.jpg), we OMIT it from the push.
        $pushImage = $thumbnailImage;
        if (!$pushImage || strpos($pushImage, 'default-image/') !== false) {
             $pushImage = null;
        }

        Log::info('[FCM_DEBUG] Notification Type: ' . $notificationType);
        Log::info('[FCM_DEBUG] Resolved List Image: ' . ($thumbnailImage ?? 'None'));
        Log::info('[FCM_DEBUG] Push Payload Image (Null if default): ' . ($pushImage ?? 'None'));

        $this->data['image'] = $thumbnailImage; // Store for notification list display

        $message = [
            "topic" => 'user_' . $notifiable->id,
            "notification" => [
                "title" => $heading,
                "body" => $msg,
            ],
            "data" => [
                "sound" => "default",
                "story_id" => "story_12345",
                "type" => $type,
                "additional_data" => $additionalData,
            ],
            "android" => [
                "priority" => "HIGH",
            ],
            "apns" => [
                "payload" => [
                    "aps" => [
                        "category" => $type,
                        "mutable-content" => 1,
                    ],
                ],
            ],
        ];

        if (!empty($pushImage)) {
            $message['notification']['image'] = $pushImage;
            $message['data']['image'] = $pushImage;
            $message['data']['picture'] = $pushImage;
            $message['data']['large_icon'] = $pushImage;
            $message['android']['notification'] = ['image' => $pushImage];
            $message['apns']['fcm_options'] = ['image' => $pushImage];
        }

        return $this->fcm([
            "message" => $message,
        ]);
    }


    function fcm($fields)
    {
        $otherSetting = \App\Models\Setting::where('name', 'projectId')->first();
        $projectID = $otherSetting->val ?? null;
        
        $access_token = $this->getAccessToken();
        $headers = [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ];

        $url = 'https://fcm.googleapis.com/v1/projects/' . $projectID . '/messages:send';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
    
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        Log::info('[FCM_DEBUG] FCM REQUEST PAYLOAD: ' . json_encode($fields));
        Log::info('[FCM_DEBUG] FCM RESPONSE (HTTP ' . $httpCode . '): ' . $response);
        Log::info('[FCM_DEBUG] FCM RESPONSE: ' . $response);
        
        curl_close($ch);
    }
    
    function getAccessToken()
    {
        try {
            $directory = storage_path('app/data');
            $credentialsFiles = File::glob($directory . '/*.json');
            
            if (!empty($credentialsFiles)) {
                $client = new Google_Client();
                $client->setAuthConfig($credentialsFiles[0]);
                $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

                $token = $client->fetchAccessTokenWithAssertion();
                
                if (isset($token['error'])) {
                    Log::error('FCM Token Error: ' . json_encode($token));
                    return null;
                }

                return $token['access_token'];
            } else {
                Log::error('FCM Error: No JSON credentials found in ' . $directory);
            }
        } catch (\Exception $e) {
            Log::error('FCM Exception in getAccessToken: ' . $e->getMessage());
        }
        return null;
    }
}
