<?php

namespace Modules\User\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\UserMultiProfile;
use App\Models\Device;
use Modules\User\Transformers\UserMultiProfileResource;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class UserMultiProfileController extends Controller
{
    public function profileList(Request $request)
    {
        $user_id = !empty($request->user_id)? $request->user_id :auth()->user()->id;

        $perPage = $request->input('per_page', 10);
        $profiles = UserMultiProfile::with('user');

        $profiles = $profiles->where('user_id', operator: $user_id)->paginate($perPage);

        $responseData = UserMultiProfileResource::collection($profiles);

        return ApiResponse::success($responseData, __('movie.profile_list'), 200);
    }

    public function saveProfile(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        if (isset($data['name']) && strlen(trim($data['name'])) > 12) {
            return ApiResponse::error(__('messages.name_max_12_characters'), 422);
        }

        $existingProfile = null;
        if (!empty($request->id)) {
            $existingProfile = UserMultiProfile::where('user_id', $user->id)
                                               ->where('id', $request->id)
                                               ->first();
        }

        $isUpdate = $existingProfile !== null;

        if ($isUpdate) {
            $profilesCheck = UserMultiProfile::where('user_id', $user->id);

            $isChild = isset($data['is_child_profile']) ? (int)$data['is_child_profile'] : 0;

            if ($profilesCheck->count('id') <= 1 && $isChild === 1) {
                return ApiResponse::error(__('messages.if_you_only_have_one_parent_profile_you_can_t_convert_it_to_a_child_profile'), 406);
            }

            $proCheck = UserMultiProfile::where([
                'is_child_profile' => 0,
                'user_id' => $user->id
            ])->where('id','!=',$request->id)->count('id');

            if ($proCheck < 1 && $isChild === 1) {
                return ApiResponse::error(__('messages.atleast_one_parent_profile_is_required'), 406);
            }
        }

        $avatar = $data['avatar'] ?? asset('storage/avatars/image/icon2.png');
        
        if (isset($data['avatar']) && filter_var($data['avatar'], FILTER_VALIDATE_URL)) {
            $parsedUrl = parse_url($data['avatar']);
            $appUrl = parse_url(config('app.url'));
            
            if (!isset($parsedUrl['host']) || $parsedUrl['host'] !== $appUrl['host']) {
                return ApiResponse::error(__('messages.external_urls_not_allowed') ?? 'External image URLs are not allowed. Please upload an image or select from available avatars.', 422);
            }
        }

        $profile_data = [
            'user_id' => $user->id,
            'name'    => $data['name'],
            'avatar'  => $avatar,
            'is_child_profile' => isset($data['is_child_profile']) ? $data['is_child_profile'] : 0,
        ];

        $profile_count = UserMultiProfile::where('user_id', $user->id)->count();

        if (!$isUpdate) {
            $max_profiles = $user->is_subscribe ? $this->getSubscriptionProfileLimit($user) : 1;

            if ($profile_count >= $max_profiles) {
                return ApiResponse::error(__('messages.profile_limit_reached_for_plan'), 406);
            }
        }

        if ($isUpdate) {
            $existingProfile->update($profile_data);
            $user_profile = $existingProfile->fresh();
        } else {
            $user_profile = UserMultiProfile::create($profile_data);
        }

        // Avatar upload
        if ($request->hasFile('file_url')) {
            $file = $request->file('file_url');
            $filename = $file->getClientOriginalName();
            $filePath = $file->storeAs('avatars', $filename, 'public');
            $avatar = setavatarBaseUrl('/storage/' . $filePath);
            $user_profile->update(['avatar' => $avatar]);
        }

        $profiles = UserMultiProfile::where('user_id', $user->id)->get();
        $responseData = UserMultiProfileResource::collection($profiles);
        $message = $isUpdate ? __('messages.profile_update') : __('messages.profile_add');

        cache::flush(['profile_list'], 'user');

        return ApiResponse::success($responseData, $message, 200, ['user_profile' => $user_profile]);
    }


private function getSubscriptionProfileLimit($user) 
{
    if (!$user->subscriptionPackage) {
        return 0;
    }

    $subscription = $user->subscriptionPackage;

    if (isset($subscription->plan_type) && !empty($subscription->plan_type)) {
        $planLimitations = json_decode($subscription->plan_type, true);

        if (is_array($planLimitations)) {
            foreach ($planLimitations as $limitation) {
                if (isset($limitation['slug']) && $limitation['slug'] === 'profile-limit') {
                    $limitData = $limitation['limit'] ?? null;
                    
                    if (is_array($limitData) && isset($limitData['value'])) {
                        return (int)$limitData['value'];
                    } elseif (is_string($limitData) || is_numeric($limitData)) {
                        return (int)$limitData;
                    }
                    
                    return 0;   
                }
            }
        }
    }

    return 0;
}

    public function getprofile(Request $request, int $id)    
    {
        
        $profile = UserMultiProfile::where('id', $request->id)->first();

        $responseData = New UserMultiProfileResource($profile);

        return ApiResponse::success($responseData, __('messages.profile_update'), 200);
    }

    public function SelectProfile(Request $request, int $id)    
{
    // Profile selection is user-specific, no cache clearing needed
    $user_id = $request->user_id ?? auth()->id();

    if($user_id)
    {
        $user = User::where('id', $user_id)->first();
    }

    $currentProfile = getCurrentProfileSession();
    $is_child_profile = $currentProfile['is_child_profile'] ?? 0;

    // Look up the target profile to check if it's a parent profile
    $targetProfile = UserMultiProfile::where('user_id', $user_id)->where('id', $id)->first();
    $isTargetParent = $targetProfile && $targetProfile->is_child_profile == 0;

    // PIN is only required when switching FROM a child profile TO a parent profile
    // (matches the frontend pin-verification.js isPinRequired logic)
    if ($is_child_profile == 1 && $isTargetParent && $user->is_parental_lock_enable == 1) {
        $pin = $request->pin ?? null;
        if (!$pin) {
            return ApiResponse::error(__('messages.parental_lock'), 406);
        }
        $pinString = implode('', $pin);
        $verfiy = User::where([['id','=',$user_id],['pin','=',$pinString]])->first();
        if(!$verfiy){
            return ApiResponse::error(__('messages.lbl_invalid_pin'), 406);
        }

    }
    $device = Device::where('user_id', $user_id)
                    ->where('device_id', $request->ip())
                    ->orderBy('id','DESC')
                    ->first();

    if ($device) {

        Device::where('user_id', $user_id)
                ->where('device_id', $request->ip())
                ->where('id','!=',$device->id)
                ->delete();

        $device->update(['active_profile' => $id]);
    } else {
        $agent = new Agent();
        $device_id = $request->getClientIp();
        $device_name =  $agent->browser();
        $platform = $agent->platform();

        $device = Device::create([
                'user_id' => $user_id,
                'device_id' => $device_id,
                'device_name' => $device_name,
                'platform' => $platform,
                  'session_id' => session()->getId(),
                'last_activity' => now(),
                'active_profile' => $id
            ]);

    }

    $profiles = UserMultiProfile::where('user_id', $user_id)->get();

    $responseData = UserMultiProfileResource::collection($profiles);

    setCurrentProfileSession(1,$id);

    return ApiResponse::success($responseData, __('movie.profile_selected'), 200);
}



    public function deleteProfile(Request $request)
    {
        $user = auth()->user();

        $profile = UserMultiProfile::where('user_id', $user->id)->where('id', $request->profile_id)->first();

        if ($profile == null) {

            $message = __('movie.profile');

            return ApiResponse::error($message, 404);
        }
  
        if ($profile->is_child_profile == 0) {
            $remainingParentProfiles = UserMultiProfile::where('user_id', $user->id)
                ->where('is_child_profile', 0)
                ->where('id', '!=', $profile->id)
                ->count();
            
            if ($remainingParentProfiles < 1) {
                return ApiResponse::error(__('messages.atleast_one_parent_profile_is_required'), 406);
            }
        }

        $profile->delete();
        $message = __('movie.profile_delete');

        clearRelatedCache(['profile_list'], 'user');
        return ApiResponse::success(null, $message, 200);
    }

}
