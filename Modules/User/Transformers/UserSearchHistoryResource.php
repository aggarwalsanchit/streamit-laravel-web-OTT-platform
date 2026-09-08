<?php

namespace Modules\User\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Subscriptions\Models\Subscription;


class UserSearchHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */

    protected $device_type;
    protected $userPlanId;


    public function __construct($resource, $device_type = null, $userPlanId = 0)
    {
        parent::__construct($resource);
        $this->device_type = $device_type;
        $this->userPlanId = $userPlanId;

    }

    
    public function toArray($request)
    {
      
        $getDeviceTypeData = Subscription::checkPlanSupportDevice($request->user_id , $this->device_type);
        $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
        $isV3 = str_contains($request->route()->uri(), 'v3');
        if($isV3){
             return [
                'id' => $this->id,
                'poster_image' => $this->poster_image,
                'details' => [
                    'id' => $this->search_id,
                    'name' => $this->search_query,
                    'type' => $this->type,
                    'release_date' => $this->release_date ? formatDate($this->release_date):null,
                    'is_device_supported' => $deviceTypeResponse['isDeviceSupported'] == true ? 1 : 0,
                    'access'=> $this->access,
                    'required_plan_level'=>$this->required_plan_level,//  optional($this->entertainment)->movie_access == 'free' ? 0 : optional($this->entertainment)->plan_id,
                    'has_content_access' => $this->has_content_access,// $this->userPlanId >= optional($this->entertainment)->plan_id ? 1 : 0, 
                    'tv_show_data' => $this->tv_show_data,
                ]
            ];
        }
        
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'profile_id' => $this->profile_id,
            'search_query' => $this->search_query,
            'type' => $this->type,
            'search_id'=>$this->search_id,
            'file_url' => $this->poster_image

        ];
    }
}
