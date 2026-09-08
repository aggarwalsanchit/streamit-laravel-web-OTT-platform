<?php

namespace Modules\Coupon\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Coupon\Models\Coupon;
use Carbon\Carbon;

class CouponDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'code' => 'WELCOME20',
                'description' => 'Welcome offer - Get 20% off ',
                'discount_type' => 'percentage',
                'discount' => 20.00,
                'start_date' => Carbon::now()->toDateString(),
                'expire_date' => Carbon::now()->addMonths(3)->toDateString(),
                'status' => true,
            ],
            [
                'code' => 'SAVE50',
                'description' => 'Save 50% on annual plans - Limited time offer',
                'discount_type' => 'percentage',
                'discount' => 50,
                'start_date' => Carbon::now()->toDateString(),
                'expire_date' => Carbon::now()->addMonths(6)->toDateString(),
                'status' => true,
            ],
            [
                'code' => 'SUMMER25',
                'description' => 'Summer special - 25% discount ',
                'discount_type' => 'percentage',
                'discount' => 25.00,
                'start_date' => Carbon::now()->toDateString(),
                'expire_date' => Carbon::now()->addDays(30)->toDateString(),
                'status' => true,
            ],
            [
                'code' => 'FLAT10',
                'description' => 'Flat $10 discount ',
                'discount_type' => 'fixed',
                'discount' => 10.00,
                'start_date' => Carbon::now()->toDateString(),
                'expire_date' => Carbon::now()->addMonths(2)->toDateString(),
                'status' => true,
            ],
            [
                'code' => 'NEWYEAR30',
                'description' => 'New Year celebration - 30% off ',
                'discount_type' => 'percentage',
                'discount' => 30.00,
                'start_date' => Carbon::now()->toDateString(),
                'expire_date' => Carbon::now()->addMonths(1)->toDateString(),
                'status' => true,
            ],
        ];

        foreach ($coupons as $couponData) {
            // Check if coupon already exists
            $existingCoupon = Coupon::where('code', $couponData['code'])->first();
            
            if (!$existingCoupon) {
                $coupon = Coupon::create($couponData);
                
                switch ($couponData['code']) {
                    case 'WELCOME20':
                        // Welcome offer - attach to Basic plans (monthly and yearly)
                        $coupon->subscriptionPlans()->attach([1, 4]);
                        break;
                    case 'SAVE50':
                        // Save 50% - attach to all yearly plans
                        $coupon->subscriptionPlans()->attach([4, 5, 6]);
                        break;
                    case 'SUMMER25':
                        // Summer special - attach to all plans
                        $coupon->subscriptionPlans()->attach([1, 2, 3, 4, 5, 6]);
                        break;
                    case 'FLAT10':
                        // Flat discount - attach to Premium and Ultimate plans
                        $coupon->subscriptionPlans()->attach([ 3, 5, 6]);
                        break;
                    case 'NEWYEAR30':
                        // New Year - attach to all plans
                        $coupon->subscriptionPlans()->attach([1, 2, 3, 4, 5, 6]);
                        break;
                }
            }
        }

    }
}
