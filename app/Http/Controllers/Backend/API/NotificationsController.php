<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function notificationList(Request $request)
    {
        $user = auth()->user();
        $user->last_notification_seen = now();
        $user->save();

        $type = isset($request->type) ? $request->type : null;
        if ($type == 'mark_as_read') {
            if (count($user->unreadNotifications) > 0) {
                $user->unreadNotifications->markAsRead();
            }
        }
        $page = 1;
        $limit = 100;
        $notifications = $user->notifications->sortByDesc('created_at')->forPage($page, $limit);
        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;
        $items = NotificationResource::collection($notifications)->toArray(request());
        $finalData = collect($items)->map(function ($item) {
            $data = $item['data'] ?? [];
            $innerData = $data['data'] ?? [];

            $id = match ($innerData['notification_type']) {
                'season_add'  => $innerData['tv_show_id'],
                'episode_add' => $innerData['tv_show_id'], 
                default       => $innerData['id'] ?? null,
            };
        
            $notifiable_id = $this->getThumbData(
                data_get($innerData, 'notification_type'),
                $item
            );
        
            return [
                'id'              => data_get($item, 'notifiable_id'),
                'notification_id' => data_get($item, 'id'),
                'is_already_read' => data_get($item, 'read_at') ? 1 : 0,
        
                'data' => array_merge([
                    'id'                => $id,
                    'notification_type' => data_get($innerData, 'notification_type'),
                    'subject'           => data_get($data, 'subject'),
                    'description'       => data_get($innerData, 'message'),
                ], $notifiable_id),
        
                'date_time' => data_get($item, 'created_at'),
            ];
        });

        $response = [
            'notification_data' => $finalData,
            'unread_notification_count'  => $all_unread_count,
            'message' => __('messages.mark_read'),
            'status' => true,
        ];

        return $response;
    }

    public function notificationCount(Request $request)
    {
        $user = auth()->user() ?? null;
        if(!$user){
            return [
                'status' => true,
                'data'  => 0,
            ];
        }
        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;
        return [
            'status' => true,
            'data'  => $all_unread_count,
        ];
    }
    private function getThumbData(?string $type, $item): array
    {
        $data = $item['data'] ?? [];
        $innerData = $data['data'] ?? [];
        $tv_show_id = $innerData['tv_show_id'] ?? null;
        $content_id = $innerData['content_id'] ?? null;
        $ppv_content_type = $innerData['content_type'] ?? null;
        $id   = $innerData['id'] ?? null;
        $content_type = $innerData['content_type'] ?? $data['content_type'] ?? 'movie';

        switch ($type) {
            case 'movie_add':
                return [
                    'thumbnail_image' => getThumbnail($innerData['movie_name'] ?? null, 'movie'),
                    'movie_data'      => ['movie_id' => $id],
                ];

            case 'episode_add':
                return [
                    'thumbnail_image' => getThumbnail($innerData['episode_name'] ?? null, 'episode'),
                    'episode_data'    => ['episode_id' => $id, 'tv_show_id' => $tv_show_id],
                ];

            case 'season_add':
                return [
                    'thumbnail_image' => getThumbnail($innerData['season_name'] ?? null, 'season'),
                    'season_data'     => ['season_id' => $id, 'tv_show_id' => $tv_show_id],
                ];

            case 'tv_show_add':
                return [
                    'thumbnail_image' => getThumbnail($innerData['tvshow_name'] ?? null, 'tv_show'),
                    'tv_show_data'    => $item['movie_data'] ?? null,
                ];

            case 'purchase_video':
            case 'rent_video':
            case 'one_time_purchase_content':
            case 'rental_content':
                $key = $type === 'purchase_video' ? 'purchase_video' : 
                       ($type === 'one_time_purchase_content' ? 'one_time_purchase_content' : 
                       ($type === 'rental_content' ? 'rental_content' : 'rent_video'));
                return [
                    'thumbnail_image' => getThumbnail($innerData['name'] ?? null, $content_type),
                    $key => [
                        'content_id' => (int)$content_id,
                        'content_type' => $ppv_content_type,
                    ],
                ];

            case 'rent_expiry_reminder':
                return ['rent_expiry_reminder' => $id];

                return ['purchase_expiry_reminder' => $id];
            
            case 'new_subscription':
            case 'expiry_plan':
            case 'subscription_expiry_reminder':
                return [
                    'thumbnail_image' => url('default-image/Default-Subscription-Image.png'),
                    'new_subscription' => ['subscription_id' => $id]
                ];

            case 'upcoming':
                $name = $innerData['name'] ?? null;
                $contentType = $innerData['content_type'] ?? 'movie';
                return [
                    'thumbnail_image' => getThumbnail($name, $contentType),
                    'upcoming_data' => [
                        'entertainment_id' => $id,
                        'content_type' => $contentType,
                        'release_date' => $innerData['release_date'] ?? null,
                    ],
                ];

            case 'continue_watch':
                $name = $innerData['name'] ?? null;
                $contentType = $innerData['content_type'] ?? 'movie';
                return [
                    'thumbnail_image' => getThumbnail($name, $contentType),
                    'continue_watch_data' => [
                        'entertainment_id' => $id,
                        'content_type' => $contentType,
                    ],
                ];

            default:
                // For all other notification types, return loader GIF from settings
                $loaderGif = \App\Models\Setting::where('name', 'loader_gif')->value('val');
                $loaderUrl = $loaderGif ? setBaseUrlWithFileName($loaderGif, 'image', 'logos') : url('img/logo/loader.gif');
                return [
                    'thumbnail_image' => $loaderUrl,
                ];
        }
    }
    public function deleteNotification(Request $request)
    {
        $data = $request->all();
        $user = auth()->user();
        if(isset($data['type']) && $data['type'] == 'all') {
            $user->notifications()->delete();
            return ApiResponse::success(null, __('messages.all_notifications_deleted_successfully'), 200);
        }
        elseif($data['id'] != null) {
            $id  = explode(',', $data['id']);
            foreach($id as $id) {
                $notification = $user->notifications()->find($id);
                if (!$notification) {
                    return ApiResponse::error(__('messages.notification_not_found'), 404);
                }
                $notification->delete();
            }
            return ApiResponse::success(null, __('messages.notification_deleted_successfully'), 200);
        }
        else {
            return ApiResponse::error(__('messages.invalid_request'), 400);
        }
    }
}
