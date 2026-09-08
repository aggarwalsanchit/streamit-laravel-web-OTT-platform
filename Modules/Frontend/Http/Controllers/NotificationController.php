<?php

namespace Modules\Frontend\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return redirect()->route('user.login');
        }

        // Mark all notifications as read when user visits the page
        $user->unreadNotifications()->update(['read_at' => now()]);
        $perpage = setting('data_table_limit',10);
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perpage);

        $all_unread_count = 0; // All notifications are now read

        return view('frontend::notifications.list', compact('notifications', 'all_unread_count'));
    }

    public function markAllAsRead()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
        }
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return response()->json(['status' => 'error', 'message' => __('frontend.kids_profile_restricted')], 403);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    public function markAsRead(int $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
        }
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return response()->json(['status' => 'error', 'message' => __('frontend.kids_profile_restricted')], 403);
        }

        $notification = $user->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['status' => 'success', 'message' => 'Notification marked as read']);
        }

        return response()->json(['status' => 'error', 'message' => 'Notification not found'], 404);
    }

    public function deleteAll()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
        }
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return response()->json(['status' => 'error', 'message' => __('frontend.kids_profile_restricted')], 403);
        }

        $user->notifications()->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.all_notifications_deleted_successfully')
        ]);
    }

    public function deleteSelected(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not authenticated'], 401);
        }
        if (getCurrentProfileSession('is_child_profile') == 1) {
            return response()->json(['status' => 'error', 'message' => __('frontend.kids_profile_restricted')], 403);
        }

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|string'
        ]);

        $ids = $request->ids;
        $deletedCount = 0;

        foreach ($ids as $id) {
            $notification = $user->notifications()->find($id);
            if ($notification) {
                $notification->delete();
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $message = $deletedCount == 1 
                ? __('messages.notification_deleted_successfully')
                : __('messages.notifications_deleted_successfully', ['count' => $deletedCount]);
            
            return response()->json([
                'status' => 'success',
                'message' => $message
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => __('messages.no_notifications_selected')
        ], 400);
    }
}
