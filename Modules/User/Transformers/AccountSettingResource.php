<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Page\Transformers\PageResource;

class AccountSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {

        $yourDevice = null;
        if ($this->your_device) {
            $device = $this->your_device;
            $yourDevice = [
                'id'             => $device->id,
                'user_id'        => $device->user_id,
                'device_id'      => $device->device_id,
                'device_name'    => $device->device_name,
                'active_profile' => $device->active_profile,
                'platform'       => $device->platform,
                'created_at'     => formatDateTimeWithTimezone($device->created_at),
                'updated_at'     => formatDateTimeWithTimezone($device->updated_at),
            ];
        }

        $otherDevices = null;
        if ($this->other_device) {
            $otherDevices = [];

            foreach ($this->other_device as $device) {
                $otherDevices[] = [
                    'id'             => $device->id,
                    'user_id'        => $device->user_id,
                    'device_id'      => $device->device_id,
                    'device_name'    => $device->device_name,
                    'active_profile' => $device->active_profile,
                    'platform'       => $device->platform,
                    'created_at'     => formatDateTimeWithTimezone($device->created_at),
                    'updated_at'     => formatDateTimeWithTimezone($device->updated_at),
                ];
            }
        }

        return [
            'is_parental_lock_enable'=> $this->is_parental_lock_enable ?? null,
            'plan_details' => $this->plan_details ?? null,
            'register_mobile_number' => $this->mobile,
            'your_device'            => $yourDevice,
            'other_device'           => $otherDevices,
            // 'page_list'=>PageResource::collection($this->page)
        ];
    }
}
