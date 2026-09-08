<?php

namespace Modules\Subscriptions\Models;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Entertainment\Models\Entertainment;
use Modules\Season\Models\Season;
use Modules\Episode\Models\Episode;
use Modules\Video\Models\Video;

class Plan extends BaseModel
{
    use SoftDeletes;

    protected $table = 'plan';

    protected $fillable = ['name', 'identifier','android_identifier','apple_identifier', 'level', 'duration', 'duration_value', 'price', 'description', 'status', 'discount', 'discount_percentage', 'total_price'];

    const CUSTOM_FIELD_MODEL = 'Modules\Subscriptions\Models\Plan';

    public function planLimitation()
    {
        return $this->hasMany(PlanLimitationMapping::class, 'plan_id', 'id')->with('limitation_data');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')->withTrashed();
    }

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_subscription_plan', 'subscription_plan_id', 'coupon_id');
    }
    
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($plan) {
            Entertainment::where('plan_id', $plan->id)
                ->update(['movie_access' => 'free', 'plan_id' => null]);

            Season::where('plan_id', $plan->id)
                ->update(['access' => 'free', 'plan_id' => null]);

            Episode::where('plan_id', $plan->id)
                ->update(['access' => 'free', 'plan_id' => null]);

            if (class_exists(Video::class)) {
                Video::where('plan_id', $plan->id)
                    ->update(['access' => 'free', 'plan_id' => null]);
            }
        });
    }


}

