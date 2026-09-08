<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Clear all existing notification template data
        DB::table('notification_template_content_mapping')->truncate();
        DB::table('notification_templates')->truncate();

        // Clear constants related to notification types
        DB::table('constants')->where('type', 'notification_type')->delete();
        DB::table('constants')->where('type', 'notification_param_button')->delete();
        DB::table('constants')->where('type', 'notification_to')->delete();

        // Run the seeder to populate fresh data
        $seeder = new \Modules\NotificationTemplate\database\seeders\NotificationTemplateSeeder();
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration is destructive, so we'll leave the down method empty
        // to prevent accidental data loss during rollback
    }
};
