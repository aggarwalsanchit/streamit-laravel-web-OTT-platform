<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\WebQrSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\TvLoginSession;

class WebQrLoginController extends Controller
{
    // Show QR login page

    // Check QR login status (for polling)
    public function checkStatus(int $id)
    {
        $qrSession = WebQrSession::findOrFail($id);

        if ($qrSession->isExpired()) {
            return response()->json(['status' => 'expired'], 410);
        }
        if ($qrSession->status === 'authenticated' && $qrSession->user_id) {
            Auth::loginUsingId($qrSession->user_id);
        }

        return response()->json(['status' => $qrSession->status]);
    }

    // API for mobile app to scan QR
    public function scan(Request $request)
    {
        $request->validate(['session_id' => 'required|uuid']);

        if($request->has('type') && $request->type == 'television') {

            $session = TvLoginSession::where('session_id', $request->session_id)->where('expires_at', '>', now())->first();

            if (!$session) {
                return response()->json(['status' => false, 'message' => 'Session not found or expired.'], 404);
            }

            $session->update([
                'user_id' => Auth::id(),
                'confirmed_at' => now()
            ]);

            return response()->json(['status' => true, 'message' => __('messages.link_tv_success')]);
        }

         if (!Auth::check()) {
            return response()->json(['message' => 'User not logged in'], 401);
        }


        $qrSession = WebQrSession::where('session_id', $request->session_id)->first();

        // Check if session is expired
        if ($qrSession->isExpired()) {
            return response()->json(['message' => 'QR session expired'], 410);
        }

        $qrSession->update([
            'user_id' => Auth::id(),
            'status' => 'authenticated',
            'confirmed_at' => now()
        ]);
        return response()->json([
            'status' => true,
            'message' => __('messages.qr_scanned_successfully')]);
    }
}
