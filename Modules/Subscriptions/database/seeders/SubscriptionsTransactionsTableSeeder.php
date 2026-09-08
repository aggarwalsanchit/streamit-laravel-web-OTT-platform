<?php

namespace Modules\Subscriptions\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SubscriptionsTransactionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('subscriptions_transactions')->delete();

        $subscriptionDates = [];
        $subscriptions = \DB::table('subscriptions')->select('id', 'created_at')->get();
        foreach ($subscriptions as $subscription) {
            $subscriptionDates[$subscription->id] = $subscription->created_at;
        }

        $transactions = [
            [
                'id' => 1,
                'subscriptions_id' => 1,
                'user_id' => 3,
                'amount' => 50,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 3,
                'updated_by' => 3,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 2,
                'subscriptions_id' => 2,
                'user_id' => 4,
                'amount' => 5,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 4,
                'updated_by' => 4,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 3,
                'subscriptions_id' => 3,
                'user_id' => 5,
                'amount' => 20,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 5,
                'updated_by' => 5,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 4,
                'subscriptions_id' => 4,
                'user_id' => 6,
                'amount' => 50,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 6,
                'updated_by' => 6,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 5,
                'subscriptions_id' => 5,
                'user_id' => 8,
                'amount' => 80,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 8,
                'updated_by' => 8,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 6,
                'subscriptions_id' => 6,
                'user_id' => 9,
                'amount' => 80,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 9,
                'updated_by' => 9,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 7,
                'subscriptions_id' => 7,
                'user_id' => 10,
                'amount' => 5,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 10,
                'updated_by' => 10,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
            [
                'id' => 8,
                'subscriptions_id' => 8,
                'user_id' => 14,
                'amount' => 20,
                'payment_type' => 'stripe',
                'payment_status' => 'paid',
                'transaction_id' => 'pi_1OnGh1FTMa5P8ht0pEWTz',
                'tax_data'=> NULL,
                'other_transactions_details' => NULL,
                'created_by' => 14,
                'updated_by' => 14,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
            ],
        ];

        foreach ($transactions as &$transaction) {
            $subscriptionDate = $subscriptionDates[$transaction['subscriptions_id']] ?? Carbon::now();
            $transaction['created_at'] = $subscriptionDate;
            $transaction['updated_at'] = $subscriptionDate;
        }

        \DB::table('subscriptions_transactions')->insert($transactions);


    }
}
