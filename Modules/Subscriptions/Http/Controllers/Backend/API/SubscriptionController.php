<?php

namespace Modules\Subscriptions\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Modules\Subscriptions\Http\Requests\SubscriptionRequest;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Subscriptions\Models\SubscriptionTransactions;
use Modules\Subscriptions\Trait\SubscriptionTrait;
use Modules\Subscriptions\Transformers\SubscriptionResource;
use Modules\Subscriptions\Transformers\PlanlimitationMappingResource;
use Modules\Tax\Models\Tax;
use Modules\Coupon\Models\Coupon;
use App\Models\UserMultiProfile;
use Illuminate\Support\Facades\Cache;


class SubscriptionController extends Controller
{
    use SubscriptionTrait;

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function saveSubscriptionDetails(SubscriptionRequest $request)
    {
        $user_id = $request->user_id ? $request->user_id : auth()->id();
        $profile_id = getCurrentProfile($user_id, $request);
        $device_type = getDeviceType($request);

        $user = User::where('id', $user_id)->first();

      
        $get_existing_plan = $this->get_user_active_plan($user_id);

      
        // Set Default Status

        $status = config('constant.SUBSCRIPTION_STATUS.PENDING');
        $start_date = date('Y-m-d H:i:s');

        if ($get_existing_plan) {
            if ($request->identifier != $get_existing_plan->identifier) {
                $get_existing_plan->update([
                    'status' => config('constant.SUBSCRIPTION_STATUS.INACTIVE'),
                ]);
                $get_existing_plan->save();
            }
        }

        $plan = Plan::where('id', $request->plan_id)->with('planLimitation')->first();
        $limitation_data = PlanlimitationMappingResource::collection($plan->planLimitation);

        $taxes = Tax::where('status', 1)->get();
        $baseAmount = $plan->total_price ?? $plan->price;

        if (!isset($plan->total_price) && $plan['discount_percentage'] > 0) {
            $baseAmount = $plan->price - ($plan->price * $plan['discount_percentage'] / 100);
        }

        $couponDiscount = 0;
        if ($request->has('coupon_id')) {
            $promotion = Coupon::where('id', $request->coupon_id)
                ->where('status', 1)
                ->whereHas('subscriptionPlans', function ($query) use ($request) {
                    $query->where('subscription_plan_id', $request->plan_id);
                })
                ->first();

            if ($promotion) {
                $couponDiscount = $promotion->discount_type == 'fixed'
                    ? $promotion->discount
                    : ($baseAmount * $promotion->discount) / 100;
            }
        }

        $subtotalAfterPromotion = max(0, $baseAmount - $couponDiscount);

        $totalTax = 0;
        foreach ($taxes as $tax) {
            if (strtolower($tax->type) == 'fixed') {
                $totalTax += $tax->value;
            } elseif (strtolower($tax->type) == 'percentage') {
                $totalTax += ($subtotalAfterPromotion * $tax->value) / 100;
            }
        }

        $totalAmount = $subtotalAfterPromotion + $totalTax;

        $end_date = $this->get_plan_expiration_date($start_date, $plan['duration'], $plan['duration_value']);

        $subscribed_plan_data = [
            'plan_id' => $request->plan_id,
            'user_id' => $user_id,
            'device_id' => $request->device_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => $status,
            'amount' => $plan['price'],
            'discount_percentage' => $plan['discount_percentage'],
            'coupon_discount' => $couponDiscount,
            'tax_amount' => $totalTax,
            'total_amount' => $totalAmount,
            'name' => $plan['name'],
            'identifier' => $plan['identifier'],
            'type' => $plan['duration'],
            'duration' => $plan['duration_value'],
            'level' => $plan['level'],
            'plan_type' => $limitation_data ? json_encode($limitation_data) : null,
            'coupon_id' => $request->coupon_id ?? null, // Store the coupon ID if provided
        ];

        $result = Subscription::create($subscribed_plan_data);

        if ($result) {
            $payment_data = [
                'subscriptions_id' => $result->id,
                'user_id' => $result->user_id,
                'amount' => $result->amount,
                'tax_data' => $taxes->isEmpty() ? null : json_encode($taxes),
                'payment_status' => $request->payment_status,
                'payment_type' => $request->payment_type,
                'transaction_id' => $request->transaction_id,
            ];
            $payment = SubscriptionTransactions::create($payment_data);

            if ($payment->payment_status == 'paid') {
                $result->status = config('constant.SUBSCRIPTION_STATUS.ACTIVE');
                $result->payment_id = $payment->id;
                $result->save();
                $user->is_subscribe = 1;
                $user->save();
                $message = __('messages.payment_completed');
            }
            $result->plan_type = json_decode($result->plan_type);
        }

        $response = new SubscriptionResource($result);
        
        $this->clearProfileDetailsV3Cache($user_id, $device_type);

        $this->sendNotificationOnsubscription('new_subscription', $response);

        return $this->sendResponse($response, __('messages.user_subscribe'));
    }

    public function getUserSubscriptionHistroy()
    {
        $user_id = auth()->id();
        $subscription = Subscription::where('user_id', $user_id)->orderBy('created_at', 'desc')->get();

        $subscription = $subscription->map(function ($item) {
            $item->start_date = !empty($item->start_date) ? formatDate($item->start_date) : null;
            $item->end_date   = !empty($item->end_date)   ? formatDate($item->end_date)   : null;
            return $item;
        });

        return $this->sendResponse(
            SubscriptionResource::collection($subscription),
            __('messages.user_subscribe_history')
        );
    }

    public function cancelSubscription(Request $request)
    {
        $user_id = $request->user_id ? $request->user_id : auth()->id();
        $subscription_plan_id = $request->id;

        $user_subscription = Subscription::where('id', $subscription_plan_id)->where('user_id', $user_id)->first();

        $user = User::where('id', $user_id)->first();

        if ($user_subscription) {
            $user_subscription->status = config('constant.SUBSCRIPTION_STATUS.CANCLE');
            $user_subscription->save();
            $user->is_subscribe = 0;
            $user->save();
        }else{
            $message = __('messages.not_subscribe_cancel');

            return ApiResponse::error($message, 404);
        }
        $response = new SubscriptionResource($user_subscription);
        $this->sendNotificationOnsubscription('cancle_subscription', $response);
        $message = __('messages.subscribe_cancel');

        return $this->sendResponse($subscription_plan_id, $message);
    }

    /**
     * Clear profile_details_v3 cache for specific user profile
     * 
     * @param int $user_id
     * @param int|null $current_profile_id
     * @param string $current_device_type
     * @return void
     */
    private function clearProfileDetailsV3Cache($user_id, $current_device_type)
    {
        // Clear cache only for the specific profile_id and device_type combination
        $cacheKey = 'profile_details_v3_' . md5(json_encode([
            'user_id' => $user_id,
            // 'device_type' => $current_device_type
        ]));          
        Cache::forget($cacheKey);
    }
}
