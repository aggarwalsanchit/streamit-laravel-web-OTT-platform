<?php

namespace Modules\Subscriptions\database\seeders;

use Illuminate\Database\Seeder;

class PlanTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('plan')->delete();

        \DB::table('plan')->insert(array (
            0 =>
            array (
                'id' => 1,
                'name' => 'Basic',
                'identifier' => 'basic',
                'android_identifier' => NULL,
                'apple_identifier' => NULL,
                'price' => 5.0,
                'discount' => 0,
                'discount_percentage' => NULL,
                'total_price' => 5.0,
                'level' => 1,
                'duration' => 'month',
                'duration_value' => 1,
                'status' => 1,
                'description' => 'The Basic Plan offers access to a limited selection of content on a weekly basis, perfect for casual viewers.',
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-07-11 04:42:21',
                'updated_at' => '2024-07-11 04:42:21',
            ),
            1 =>
            array (
                'id' => 2,
                'name' => 'Premium Plan',
                'identifier' => 'premium_plan',
                'android_identifier' => NULL,
                'apple_identifier' => NULL,
                'price' => 20.0,
                'discount' => 0,
                'discount_percentage' => NULL,
                'total_price' => 20.0,
                'level' => 2,
                'duration' => 'month',
                'duration_value' => 1,
                'status' => 1,
                'description' => '<p>The Premium Plan provides monthly access to a wider range of content, including exclusive shows and features.</p>',
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-07-11 04:43:13',
                'updated_at' => '2024-10-08 09:28:11',
            ),
            2 =>
            array (
                'id' => 3,
                'name' => 'Ultimate Plan',
                'identifier' => 'ultimate_plan',
                'android_identifier' => NULL,
                'apple_identifier' => NULL,
                'price' => 50.0,
                'discount' => 0,
                'discount_percentage' => NULL,
                'total_price' => 50.0,
                'level' => 3,
                'duration' => 'month',
                'duration_value' => 3,
                'status' => 1,
                'description' => 'The Ultimate Plan offers quarterly access with additional perks, such as early access to new releases and special content.',
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-07-11 04:45:14',
                'updated_at' => '2024-07-11 04:45:14',
            ),
            3 =>
            array (
                'id' => 4,
                'name' => 'Basic',
                'identifier' => 'basic',
                'android_identifier' => NULL,
                'apple_identifier' => NULL,
                'price' => 100.0,
                'discount' => 0,
                'discount_percentage' => NULL,
                'total_price' => 100.0,
                'level' => 4,
                'duration' => 'year',
                'duration_value' => 1,
                'status' => 1,
                'description' => 'The Basic Plan offers access to a limited selection of content on a weekly basis, perfect for casual viewers.',
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-07-11 04:42:21',
                'updated_at' => '2024-07-11 04:42:21',
            ),
            4 =>
            array (
                'id' => 5,
                'name' => 'Premium Plan',
                'identifier' => 'premium_plan',
                'android_identifier' => NULL,
                'apple_identifier' => NULL,
                'price' => 200.0,
                'discount' => 0,
                'discount_percentage' => NULL,
                'total_price' => 200.0,
                'level' => 5,
                'duration' => 'year',
                'duration_value' => 1,
                'status' => 1,
                'description' => '<p>The Premium Plan provides monthly access to a wider range of content, including exclusive shows and features.</p>',
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-07-11 04:43:13',
                'updated_at' => '2024-10-08 09:28:11',
            ),
            5 =>
            array (
                'id' => 6,
                'name' => 'Ultimate Plan',
                'identifier' => 'ultimate_plan',
                'android_identifier' => NULL,
                'apple_identifier' => NULL,
                'price' => 500.0,
                'discount' => 0,
                'discount_percentage' => NULL,
                'total_price' => 500.0,
                'level' => 6,
                'duration' => 'year',
                'duration_value' => 1,
                'status' => 1,
                'description' => 'The Ultimate Plan offers quarterly access with additional perks, such as early access to new releases and special content.',
                'created_by' => 2,
                'updated_by' => 2,
                'deleted_by' => NULL,
                'deleted_at' => NULL,
                'created_at' => '2024-07-11 04:45:14',
                'updated_at' => '2024-07-11 04:45:14',
            ),
        ));


    }
}
