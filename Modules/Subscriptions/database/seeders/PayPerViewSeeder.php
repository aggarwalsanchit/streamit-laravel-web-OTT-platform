<?php

namespace Modules\Subscriptions\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PayPerViewSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('pay_per_views')->delete();

        \DB::table('payperviewstransactions')->delete();

        // Prepare pay_per_views data with first_play_date as created_at and updated_at
        $payPerViews = [
            [
                'id' => 1,
                'user_id' => 5,
                'movie_id' => 1,
                'type' => 'movie',
                'content_price' => 9.99,
                'price' => 7.99,
                'discount_percentage' => 20.00,
                'view_expiry_date' => '2025-12-22 10:30:00',
                'access_duration' => '1',
                'available_for' => '2',
            ],
            [
                'id' => 2,
                'user_id' => 6,
                'movie_id' => 2,
                'type' => 'movie',
                'content_price' => 12.99,
                'price' => 12.99,
                'discount_percentage' => NULL,
                'view_expiry_date' => '2025-12-21 14:15:00',
                'access_duration' => '2',
                'available_for' => '3',
            ],
            [
                'id' => 3,
                'user_id' => 3,
                'movie_id' => 25,
                'type' => 'movie',
                'content_price' => 15.99,
                'price' => 11.99,
                'discount_percentage' => 25.00,
                'view_expiry_date' => '2025-12-26 09:00:00',
                'access_duration' => '3',
                'available_for' => '4',
            ],
            [
                'id' => 4,
                'user_id' => 6,
                'movie_id' => 4,
                'type' => 'movie',
                'content_price' => 8.99,
                'price' => 8.99,
                'discount_percentage' => NULL,
                'view_expiry_date' => '2025-12-20 18:45:00',
                'access_duration' => '1',
                'available_for' => '2 ',
            ],
            [
                'id' => 5,
                'user_id' => 8,
                'movie_id' => 26,
                'type' => 'movie',
                'content_price' => 19.99,
                'price' => 14.99,
                'discount_percentage' => 25.00,
                'view_expiry_date' => '2025-12-31 20:00:00',
                'access_duration' => '3 ',
                'available_for' => '5',
            ],
        ];

        // Set first_play_date, created_at, and updated_at based on first_play_date
        foreach ($payPerViews as &$payPerView) {
            $first_play_date = Carbon::today()->subDays(rand(25, 30));
            $payPerView['first_play_date'] = $first_play_date;
            $payPerView['created_at'] = $first_play_date;
            $payPerView['updated_at'] = $first_play_date;
        }

        \DB::table('pay_per_views')->insert($payPerViews);

        // Create a map of pay_per_view_id to first_play_date for easy lookup
        $payPerViewDates = [];
        foreach ($payPerViews as $payPerView) {
            $payPerViewDates[$payPerView['id']] = $payPerView['first_play_date'];
        }

        // Prepare transactions data with dates matching their corresponding pay_per_view
        $transactions = [
            [
                'id' => 1,
                'pay_per_view_id' => 1,
                'user_id' => 5,
                'amount' => 7.99,
                'payment_type' => 'stripe',
                'transaction_id' => 'txn_ppv_1001',
            ],
            [
                'id' => 2,
                'pay_per_view_id' => 2,
                'user_id' => 6,
                'amount' => 12.99,
                'payment_type' => 'paypal',
                'transaction_id' => 'txn_ppv_1002',
            ],
            [
                'id' => 3,
                'pay_per_view_id' => 3,
                'user_id' => 3,
                'amount' => 11.99,
                'payment_type' => 'stripe',
                'transaction_id' => 'txn_ppv_1003',
            ],
            [
                'id' => 4,
                'pay_per_view_id' => 4,
                'user_id' => 6,
                'amount' => 8.99,
                'payment_type' => 'stripe',
                'transaction_id' => 'txn_ppv_1004',
            ],
            [
                'id' => 5,
                'pay_per_view_id' => 5,
                'user_id' => 8,
                'amount' => 14.99,
                'payment_type' => 'stripe',
                'transaction_id' => 'txn_ppv_1005',
            ],
        ];

        // Set created_at and updated_at based on corresponding pay_per_view's first_play_date
        foreach ($transactions as &$transaction) {
            $transactionDate = $payPerViewDates[$transaction['pay_per_view_id']] ?? Carbon::now();
            $transaction['created_at'] = $transactionDate;
            $transaction['updated_at'] = $transactionDate;
        }

        \DB::table('payperviewstransactions')->insert($transactions);


    }
}
