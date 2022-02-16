<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Validator;
//use App\Models\Preference;

class NotificationController extends Controller
{
    public function getNotificationDetail(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $unread = $user->unreadNotifications->count();
        $read = $user->readNotifications->count();
        $all = $user->notifications->count();
        $notifications = $user->notifications;
        
        return response()->json([
            'unread' => $unread,
            'read' => $read,
            'total' => $all,
            'notification' => $notifications
        ], 200);

    }

    public function getNotificationSummary(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $unread = $user->unreadNotifications->count();
        $read = $user->readNotifications->count();
        $all = $user->notifications->count();
        $notifications = $user->notifications;

        return response()->json([
            'unread' => $unread,
            'read' => $read,
            'total' => $all,
        //    'message' => $notifications['data']['message'],
        //    'subject' => $notifications['data']['subject']
        ], 200);

    }

    public function getUnreadNotification(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $notifications = $user->unreadNotifications;
        
        return response()->json([
            'notifications' => $notifications
        ], 200);

    }

    public function getReadNotification(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $notifications = $user->readNotifications;
        
        return response()->json([
            'notifications' => $notifications
        ], 200);

    }

    public function markAllAsUnread(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $user->Notifications->markAsUnread();
        
        return response()->json([
            'success' => true,
            'message' => 'All notifications have been marked as unread!'
        ], 200);

    }

    public function markAllAsRead(Request $request)
    {
        $user = User::find(auth()->user()->id);

        $user->Notifications->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'All notifications have been marked as read!'
        ], 200);

    }

    public function markRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $user = User::find(auth()->user()->id);

        $user->Notifications->where('id', $request->notification_id)->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification has been marked as read!'
        ], 200);

    }

    public function markUnread(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string'
        ]);
        
        if ($validator->fails())
        {
            return response()->json(['errors'=>$validator->errors()->all()], 412);
        }

        $user = User::find(auth()->user()->id);

        $user->Notifications->where('id', $request->notification_id)->markAsUnread();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification has been marked as unread!'
        ], 200);

    }

    public function disable()
    {
        auth()->user()->allows_notification = 0;
        $disabled = auth()->user()->save();
        if ($disabled) return response()->json([
            'success' => true,
            'message' => 'You have disabled notification! You will no longer receive notifications.'
        ], 200);

        return response()->json([
            'success' => false,
            'message' => 'Sorry, notifications could not be disabled. Please, try again.'
        ], 500);
    }

    public function enable()
    {
        auth()->user()->allows_notification = 1;
        $enabled = auth()->user()->save();
        if ($enabled) return response()->json([
            'success' => true,
            'message' => 'You have successfullyenabled notification!'
        ], 200);

        return response()->json([
            'success' => false,
            'message' => 'Sorry, notifications could not be enabled. Please, try again.'
        ], 500);
    }

}
