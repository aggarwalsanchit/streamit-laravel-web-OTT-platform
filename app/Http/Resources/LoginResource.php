<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $planDetails = null;

        if (!empty($this->plan_details)) {
            $planDetails = $this->plan_details instanceof \Illuminate\Contracts\Support\Arrayable
                ? $this->plan_details->toArray()
                : (array) $this->plan_details;
            $planDetails['start_date'] = !empty($planDetails['start_date'])
                ? formatDateTimeWithTimezone($planDetails['start_date'],'date')
                : null;

            $planDetails['end_date'] = !empty($planDetails['end_date'])
                ? formatDateTimeWithTimezone($planDetails['end_date'],'date')
                : null;

             $planDetails['created_at'] = !empty($planDetails['created_at'])
                ? formatDateTimeWithTimezone($planDetails['created_at'],'date')
                : null;

            $planDetails['updated_at'] = !empty($planDetails['updated_at'])
                ? formatDateTimeWithTimezone($planDetails['updated_at'],'date')
                : null;

            if (!empty($planDetails['plan_type'])) {
                $planDetails['plan_type'] = json_decode($planDetails['plan_type']);
            }
        }

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'login_type' => $this->login_type,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth ? formatDateTimeWithTimezone($this->date_of_birth,'date'):null,
            'email_verified_at' => $this->email_verified_at ? formatDateTimeWithTimezone($this->email_verified_at) : null,
            'is_banned' => $this->is_banned,
            'is_subscribe' => $this->is_subscribe,
            'status' => $this->status,
            'last_notification_seen' => $this->last_notification_seen ? formatDateTimeWithTimezone($this->last_notification_seen) : null,
            'created_at' => $this->created_at ?  formatDateTimeWithTimezone($this->created_at) : null,
            'updated_at' => $this->updated_at ? formatDateTimeWithTimezone($this->updated_at) : null,
            'deleted_at' => $this->deleted_at ? formatDateTimeWithTimezone($this->deleted_at) : null,
            'short_drama_session_token' => $this->short_drama_session_token ?? null,
            'api_token' => $this->api_token,
            'full_name' => $this->full_name,
            'pin' => $this->pin ?? "",
            'otp' => $this->otp,
            'is_user_exist' => true,
            'profile_image' => setBaseUrlWithFileName($this->file_url, 'image', 'users'), //$this->media->pluck('original_url')->first(),
            'country_code' => $this->country_code,
            'media' => $this->media,
            'plan_details' => $planDetails ?? null,
            'session_id' => $this->session_id,
            'address' => $this->address,
        ];
    }
}
