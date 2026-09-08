<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\User\Transformers\UserMultiProfileResource;


class UserProfileResourceV3 extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {

        $continueWatch = $this->continueWatchnew;
        $watchlist = $this->watchList;
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name.' '.$this->last_name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth ? formatDate($this->date_of_birth):null,
            'login_type' => $this->login_type,
            'email_verified_at' => $this->email_verified_at ? formatDate($this->email_verified_at) . ' ' . formatTime($this->email_verified_at) : null,
            'profile_image' => setBaseUrlWithFileName($this->file_url, 'image', 'users'),
            'country_code' => $this->country_code,
            'address' => $this->address,
            'plan_details' => $this->plan_details ? $this->transformPlanDetails($this->plan_details) : null,
            'watching_profiles' =>UserMultiProfileResource::collection($this->watching_profiles),
        ];
    }

    /**
     * Transform plan details by removing unwanted keys and formatting dates
     */
    private function transformPlanDetails($planDetails)
    {
        if (!$planDetails) {
            return null;
        }

        // Convert to array if it's an object
        $planArray = is_object($planDetails) ? $planDetails->toArray() : $planDetails;

        // Remove the unwanted keys
        $keysToRemove = [
            'payment_id',
            'device_id',
            'created_by',
            'updated_by',
            'deleted_by',
            'deleted_at',
            'created_at',
            'updated_at'
        ];

        foreach ($keysToRemove as $key) {
            unset($planArray[$key]);
        }

        // Format date fields
        if (isset($planArray['start_date']) && $planArray['start_date']) {
            $planArray['start_date'] = formatDate($planArray['start_date']);
        }

        if (isset($planArray['end_date']) && $planArray['end_date']) {
            $planArray['end_date'] = formatDate($planArray['end_date']);
        }

        return $planArray;
    }
}
