<?php

namespace Modules\Frontend\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\UserMultiProfile;
use Modules\User\Transformers\UserMultiProfileResource;
use Auth;
use Hash;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function securityControl()
    {
        if(getCurrentProfileSession('is_child_profile') == 1){
            return redirect()->route('user.login');
        }

        $user = Auth::user();

        $Profile = $user->userMultiProfile;
        
        $userProfile = UserMultiProfileResource::collection($Profile);

        return view('frontend::securityControl',compact('user','userProfile'));
    }


    /**
     * Display a listing of the resource.
     */
    public function editProfile()
    {
        $user =Auth::user();

        $dev = Device::where('user_id', $user->id)
                ->where('device_id', request()->ip())
                ->orderBy('id','DESC')
                ->get();

        if(count($dev) > 1)
        {
            Device::where('user_id', $user->id)
                ->where('device_id', request()->ip())
                ->where('id','!=',$dev[0]->id)
                ->delete();
        }

        $Profile = $user->userMultiProfile;
        $profileCount = $Profile->count();
        $userProfile = UserMultiProfileResource::collection($Profile);

        return view('frontend::editProfile',compact('user','userProfile','profileCount'));
    }

    public function updateProfile()
    {
        if(getCurrentProfileSession('is_child_profile') == 1 || !Auth::check()){
            return redirect()->route('user.login');
        }

        $user =Auth::user();

        return view('frontend::updateProfile',compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
public function destroy(UserMultiProfile $profile)
{
    $user = auth()->user();

    if ($profile->user_id !== $user->id) {
        return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
    }

    if ($profile->is_child_profile == 0) {
        $remainingParentProfiles = UserMultiProfile::where('user_id', $user->id)
            ->where('is_child_profile', 0)
            ->where('id', '!=', $profile->id)
            ->count();
        
        if ($remainingParentProfiles < 1) {
            return response()->json([
                'success' => false,
                'message' => __('messages.atleast_one_parent_profile_is_required')
            ], 406);
        }
    }

    $profile->delete();

    return response()->json([
        'success' => true,
        'message' => __('messages.profile_deleted_successfully'),
        'data' => UserMultiProfileResource::collection(UserMultiProfile::where('user_id', $user->id)->get())
    ]);
}

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => ['required','confirmed','min:8','different:old_password',
            ],
        ]);

        if (!Hash::check($request->old_password, auth()->user()->password)) {
            return response()->json([
                'success' => false,
                'errors' => ['old_password' => 'The old password is incorrect.'],
            ], 422);
        }

        auth()->user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        $user = auth()->user();


        // sendNotification([
        //     'notification_type' => 'change_password',
        //     'user_id' => $user->id,
        //     'user_name' => $user->full_name ?? $user->name ?? $user->username,
        // ]);


        $notificationData = [
        'notification_type' => 'change_password',
             'user_id' => $user->id,
            'user_name' => $user->full_name ?? $user->name ?? $user->username,
        ];

    SendBulkNotification::dispatch($notificationData)->onQueue('notifications');
        return response()->json(['success' => true]);
    }


    public function manageProfile()
{
    $user = Auth::user();

    $Profile = UserMultiProfile::where('user_id', $user->id)->get();
    $profileCount = $Profile->count();
    $userProfile = UserMultiProfileResource::collection($Profile);

    return view('frontend::manageProfile', compact('user', 'userProfile', 'profileCount'));
}

public function changePassword()
{
    if(getCurrentProfileSession('is_child_profile') == 1 || !Auth::check()){
        return redirect()->route('user.login');
    }

    return view('frontend::changePassword');
}

}
