<?php

namespace Modules\NotificationTemplate\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Constant\Models\Constant;
use Modules\NotificationTemplate\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        /*
         * NotificationTemplates Seed
         * ------------------
         */



        $types = [

            [
                'type' => 'notification_type',
                'value' => 'change_password',
                'name' => 'Change Password',
            ],
            [
                'type' => 'notification_type',
                'value' => 'forget_email_password',
                'name' => 'Forget Email/Password',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'id',
                'name' => 'ID',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'user_name',
                'name' => 'User Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'description',
                'name' => 'Description / Note',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'logged_in_user_fullname',
                'name' => 'Your Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'logged_in_user_role',
                'name' => 'Your Position',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'user_id',
                'name' => 'User\' ID',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'user_password',
                'name' => 'User Password',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'site_url',
                'name' => 'Site URL',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'episode_name',
                'name' => 'Episode Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'movie_name',
                'name' => 'Movie Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'season_name',
                'name' => 'Season Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'tvshow_name',
                'name' => 'TV Show Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'end_date',
                'name' => 'Expiry Date',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'start_date',
                'name' => 'Start Date',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'subscription_plan',
                'name' => 'Plan Name',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'content_type',
                'name' => 'Content Type',
            ],
            [
                'type' => 'notification_to',
                'value' => 'user',
                'name' => 'User',
            ],
            [
                'type' => 'notification_to',
                'value' => 'admin',
                'name' => 'Admin',
            ],
            [
                'type' => 'notification_to',
                'value' => 'demo_admin',
                'name' => 'Demo Admin',
            ],
            [
                'type' => 'notification_type',
                'value' => 'tv_show_add',
                'name' => 'TV Show Add',
            ],
            [
                'type' => 'notification_type',
                'value' => 'movie_add',
                'name' => 'Movie Add',
            ],
            [
                'type' => 'notification_type',
                'value' => 'episode_add',
                'name' => 'Episode Add',
            ],
            [
                'type' => 'notification_type',
                'value' => 'season_add',
                'name' => 'Season Add',
            ],
            [
                'type' => 'notification_type',
                'value' => 'new_subscription',
                'name' => 'New Subscription',
            ],
            [
                'type' => 'notification_type',
                'value' => 'purchase_video',
                'name' => 'One Time Purchase Content',
            ],
            [
                'type' => 'notification_type',
                'value' => 'rent_video',
                'name' => 'Rental Content',
            ],
            [
                'type' => 'notification_type',
                'value' => 'rent_expiry_reminder',
                'name' => 'Rental Is Expire Soon',
            ],
            [
                'type' => 'notification_type',
                'value' => 'purchase_expiry_reminder',
                'name' => 'One Time Purchase Is Expired Soon',
            ],
            [
                'type' => 'notification_type',
                'value' => 'cancle_subscription',
                'name' => 'Cancle Subscription',
            ],
            [
                'type' => 'notification_type',
                'value' => 'video_add',
                'name' => 'Video Add',
            ],
            [
                'type' => 'notification_type',
                'value' => 'subscription_expiry_reminder',
                'name' => 'Subscription Expiry Reminder',
            ],
            [
                'type' => 'notification_type',
                'value' => 'expiry_plan',
                'name' => 'Expiry Plan',
            ],
            [
                'type' => 'notification_type',
                'value' => 'upcoming',
                'name' => 'Upcoming',
            ],
            [
                'type' => 'notification_type',
                'value' => 'continue_watch',
                'name' => 'Continue Watch',
            ],
            [
                'type' => 'notification_type',
                'value' => 'parental_control_otp',
                'name' => 'Parental Control OTP',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'otp',
                'name' => 'OTP Code',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'payment_method',
                'name' => 'Payment Method',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'end_date',
                'name' => 'End Date',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'price',
                'name' => 'Price',
            ],

            [
                'type' => 'notification_param_button',
                'value' => 'discount',
                'name' => 'Discount',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'coupon_discount',
                'name' => 'Coupon Discount',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'Tax',
                'name' => 'Tax',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'total_amount',
                'name' => 'Total Amount',
            ],
            [
                'type' => 'notification_param_button',
                'value' => 'duration',
                'name' => 'Duration',
            ],



        ];

        foreach ($types as $value) {
            Constant::updateOrCreate(['type' => $value['type'], 'value' => $value['value']], $value);
        }

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('notification_templates')->delete();
        DB::table('notification_template_content_mapping')->delete();




        $template = NotificationTemplate::create([
            'type' => 'change_password',
            'name' => 'change_password',
            'label' => 'Change Password',
            'status' => 1,
            'to' => '["user","admin","demo_admin"]',
            // Enable both email and push for password change confirmation
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping (sent AFTER password is actually changed)
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your password has been changed successfully.',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Password Change Successful',
            'notification_subject' => 'Your Password Has Been Changed',
            'notification_template_detail' => 'Hello [[ user_name ]], your password has been changed successfully for your account.',
            'template_detail' => '
            <h2>Password Change Successful</h2>
            <p>Hello [[ user_name ]],</p>
            <p>Your password has been changed successfully.</p>
            <br>
            <p>Thank you,</p>
            <p>[[ app_name ]]</p>
          ',
        ]);

        

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Admin password change notification sent.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Change Password',
            'notification_subject' => 'Admin Password Change Alert',
            'notification_template_detail' => 'Admin password change detected for [[ user_name ]] - Security verification required.',
            'template_detail' => '
            <p>Subject: Admin Password Change Alert</p>
            <p>Dear [[ user_name ]],</p>
            <p>&nbsp;</p>
            <p>This is an administrative notification regarding a password change for your admin account. As an administrator, please ensure this change was authorized and follows our security protocols.</p>
            <p>&nbsp;</p>
            <p>Security Recommendations:</p>
            <p>&nbsp;</p>
            <p>• Verify the change was initiated by you</p>
            <p>• Use a strong, unique password</p>
            <p>• Enable two-factor authentication if not already active</p>
            <p>• Review recent account activities</p>
            <p>&nbsp;</p>
            <p>If you suspect unauthorized access, immediately:</p>
            <p>• Contact system security team</p>
            <p>• Review access logs</p>
            <p>• Update other related credentials</p>
            <p>&nbsp;</p>
            <p>Best regards,</p>
            <p>System Security Team<br />[[ company_name ]]</p>
          ',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Admin password change notification sent.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Change Password',
            'notification_subject' => 'Admin Password Change Alert',
            'notification_template_detail' => 'Admin password change detected for [[ user_name ]] - Security verification required.',
            'template_detail' => '
            <p>Subject: Admin Password Change Alert</p>
            <p>Dear [[ user_name ]],</p>
            <p>&nbsp;</p>
            <p>This is an administrative notification regarding a password change for your admin account. As an administrator, please ensure this change was authorized and follows our security protocols.</p>
            <p>&nbsp;</p>
            <p>Security Recommendations:</p>
            <p>&nbsp;</p>
            <p>• Verify the change was initiated by you</p>
            <p>• Use a strong, unique password</p>
            <p>• Enable two-factor authentication if not already active</p>
            <p>• Review recent account activities</p>
            <p>&nbsp;</p>
            <p>If you suspect unauthorized access, immediately:</p>
            <p>• Contact system security team</p>
            <p>• Review access logs</p>
            <p>• Update other related credentials</p>
            <p>&nbsp;</p>
            <p>Best regards,</p>
            <p>System Security Team<br />[[ company_name ]]</p>
          ',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'forget_email_password',
            'name' => 'forget_email_password',
            'label' => 'Forget Email/Password',
            'status' => 1,
            'to' => '["user"]',
            // Enable both email and push for reset-link notification
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping (sent WHEN reset is requested, BEFORE password is changed)
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'We have emailed you password reset instructions. Open the email and click the link to reset your password.',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Password Reset Request',
            'notification_subject' => 'Reset Your Password',
            'notification_template_detail' => 'Hello [[ user_name ]], we received a request to reset the password for your account. Use the link in this email to create a new password.',
            'template_detail' => '
                <h2>Password Reset Instructions</h2>
                <p>Hello [[ user_name ]],</p>
                <p>We received a request to reset the password for your account.</p>
                <p>To choose a new password, <a href="[[ password_reset_link ]]">Click here!</a></p>
                <p>If you did not request a password reset, you can safely ignore this email and your current password will remain unchanged.</p>
                <br>
                <p>Thank you,</p>
                <p>[[ app_name ]]</p>
          ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User password has been changed successfully.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Password Change Successful',
            'notification_subject' => 'Password Change Alert',
            'notification_template_detail' => 'User [[ user_name ]] has successfully changed their account password.',
            'template_detail' => '
            <p>Subject: Password Change Alert</p>
            <p>&nbsp;</p>
            <p>User [[ user_name ]] has successfully changed their account password.</p>
            <br>
            <p>Thank you,</p>
            <p>[[ app_name ]]</p>
          ',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User password has been changed successfully.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Password Change Successful',
            'notification_subject' => 'Password Change Alert',
            'notification_template_detail' => 'User [[ user_name ]] has successfully changed their account password.',
            'template_detail' => '
            <p>Subject: Password Change Alert</p>
            <p>&nbsp;</p>
            <p>User [[ user_name ]] has successfully changed their account password.</p>
            <br>
            <p>Thank you,</p>
            <p>[[ app_name ]]</p>
          ',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'tv_show_add',
            'name' => 'tv_show_add',
            'label' => 'TV Show Added',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '0', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New TV show "[[ tvshow_name ]]" has been added to our collection!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'TV Show Added!',
            'notification_subject' => 'New TV Show Available',
            'notification_template_detail' => 'New TV show "[[ tvshow_name ]]" added to collection - Start watching now!',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>Great news! A new TV show "<strong>[[ tvshow_name ]]</strong>" has been added to our collection.</p><p>Start watching now and enjoy the latest entertainment!</p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'TV show "[[ tvshow_name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'TV Show Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'TV show "[[ tvshow_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The TV show "<strong>[[ tvshow_name ]]</strong>" has been successfully added to the content management system.</p><p>Content Details:</p><ul><li>Show Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li></ul><p>Please review and verify the content details.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'TV show "[[ tvshow_name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'TV Show Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'TV show "[[ tvshow_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The TV show "<strong>[[ tvshow_name ]]</strong>" has been successfully added to the content management system.</p><p>Content Details:</p><ul><li>Show Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li></ul><p>Please review and verify the content details.</p>',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'movie_add',
            'name' => 'movie_add',
            'label' => 'Movie Added',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '0', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New movie "[[ movie_name ]]" is now available for streaming!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Movie Added!',
            'notification_subject' => 'New Movie Available',
            'notification_template_detail' => 'New movie "[[ movie_name ]]" available for streaming - Grab your popcorn!',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>Exciting news! A new movie "<strong>[[ movie_name ]]</strong>" is now available for streaming.</p><p>Grab your popcorn and enjoy the latest entertainment!</p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Movie "[[ movie_name ]]" has been successfully added to the content system.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Movie Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Movie "[[ movie_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The movie "<strong>[[ movie_name ]]</strong>" has been successfully added to the content management system.</p><p>Content Details:</p><ul><li>Movie Title: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the content details and metadata.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Movie "[[ movie_name ]]" has been successfully added to the content system.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Movie Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Movie "[[ movie_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The movie "<strong>[[ movie_name ]]</strong>" has been successfully added to the content management system.</p><p>Content Details:</p><ul><li>Movie Title: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the content details and metadata.</p>',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'episode_add',
            'name' => 'episode_add',
            'label' => 'Episode Added',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '0', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New episode "[[ episode_name ]]" is now available!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Episode Added!',
            'notification_subject' => 'New Episode Available',
            'notification_template_detail' => 'New episode "[[ episode_name ]]" available - Don\'t miss out!',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>Great news! A new episode "<strong>[[ episode_name ]]</strong>" is now available for streaming.</p><p>Don\'t miss out on the latest episode!</p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Episode "[[ episode_name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Episode Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Episode "[[ episode_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The episode "<strong>[[ episode_name ]]</strong>" has been successfully added to the content management system.</p><p>Episode Details:</p><ul><li>Episode Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the episode details and ensure proper metadata.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Episode "[[ episode_name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Episode Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Episode "[[ episode_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The episode "<strong>[[ episode_name ]]</strong>" has been successfully added to the content management system.</p><p>Episode Details:</p><ul><li>Episode Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the episode details and ensure proper metadata.</p>',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'season_add',
            'name' => 'season_add',
            'label' => 'Season Added',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '0', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New season "[[ season_name ]]" is now available for streaming!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Season Added!',
            'notification_subject' => 'New Season Available',
            'notification_template_detail' => 'New season "[[ season_name ]]" available - Get ready for hours of entertainment!',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>Exciting news! A new season "<strong>[[ season_name ]]</strong>" is now available for streaming.</p><p>Get ready for hours of entertainment!</p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Season "[[ season_name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Season Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Season "[[ season_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The season "<strong>[[ season_name ]]</strong>" has been successfully added to the content management system.</p><p>Season Details:</p><ul><li>Season Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the season details and episode structure.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Season "[[ name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Season Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Season "[[ name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The season "<strong>[[ name ]]</strong>" has been successfully added to the content management system.</p><p>Season Details:</p><ul><li>Season Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the season details and episode structure.</p>',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'new_subscription',
            'name' => 'new_subscription',
            'label' => 'New User Subscribed',
            'status' => 1,
            'to' => '["admin","demo_admin","user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Welcome to our streaming platform! Your subscription is now active.',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'New User is subscribe!',
            'notification_subject' => 'Welcome - Subscription Active!',
            'notification_template_detail' => 'Welcome [[ user_name ]]! Your subscription is now active - Start exploring our content library.',
            'template_detail' => '<h2>Welcome to  [[ app_name ]]🎉</h2><p>Your subscription has been activated successfully.</p><br><h2>Subscription Details</h2><p>User: [[ user_name ]]</p><p>Email: [[ user_email ]]</p><p>Contact No: [[ user_mobile ]]</p><p>Plan: [[ subscription_plan ]]</p><p>End Date: [[ end_date ]]</p><p>Amount: [[ amount_formatted ]]</p><p>Tax Amount: [[ tax_amount_formatted ]]</p><p>Total Amount: [[ total_amount_formatted ]]</p><p>Duration: [[ duration_formatted ]] </p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New user subscription: [[ user_name ]] has joined the platform.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'New User is subscribe!',
            'notification_subject' => 'New Subscription - Review Required',
            'notification_template_detail' => 'New user [[ user_name ]] subscribed - Plan: [[ subscription_plan ]]',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>A new user has subscribed to the platform.</p><p>Subscription Details:</p><ul><li>New User: [[ user_name ]]</li><li>Subscription Date: [[ start_date ]]</li><li>Plan Type: [[ subscription_plan ]]</li><li>Status: Active</li></ul><p>Please review the subscription details and welcome the new user.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New user subscription: [[ user_name ]] has joined the platform.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'New User is subscribe!',
            'notification_subject' => 'New Subscription - Review Required',
            'notification_template_detail' => 'New user [[ user_name ]] subscribed - Plan: [[ subscription_plan ]]',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>A new user has subscribed to the platform.</p><p>Subscription Details:</p><ul><li>New User: [[ user_name ]]</li><li>Subscription Date: [[ start_date ]]</li><li>Plan Type: [[ subscription_plan ]]</li><li>Status: Active</li></ul><p>Please review the subscription details and welcome the new user.</p>',
        ]);
        $template = NotificationTemplate::create([
            'type' => 'cancle_subscription',
            'name' => 'cancle_subscription',
            'label' => 'User Cancel Subscription',
            'status' => 1,
            'to' => '["admin","demo_admin","user"]',
            'channels' => ['IS_MAIL' => '0', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your subscription has been cancelled. We\'re sorry to see you go.',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'A User is cancle subscribe!',
            'notification_subject' => 'Subscription Cancelled',
            'notification_template_detail' => 'Subscription cancelled for [[ user_name ]] - You can reactivate anytime.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>We\'re sorry to see you go! Your subscription has been cancelled as requested.</p><p>You can reactivate your subscription anytime by visiting your account settings.</p><p>Thank you for being part of our community!</p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User [[ user_name ]] has cancelled their subscription.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'A User is cancle subscribe!',
            'notification_subject' => 'Subscription Cancelled - Review Required',
            'notification_template_detail' => 'User [[ user_name ]] cancelled subscription',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>A user has cancelled their subscription.</p><p>Cancellation Details:</p><ul><li>User: [[ user_name ]]</li><li>Cancellation Date: [[ end_date ]]</li><li>Reason: [[ cancellation_reason ]]</li><li>Previous Plan: [[ subscription_plan ]]</li></ul><p>Please review and consider reaching out to understand their feedback.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User [[ user_name ]] has cancelled their subscription.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'A User is cancle subscribe!',
            'notification_subject' => 'Subscription Cancelled - Review Required',
            'notification_template_detail' => 'User [[ user_name ]] cancelled subscription',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>A user has cancelled their subscription.</p><p>Cancellation Details:</p><ul><li>User: [[ user_name ]]</li><li>Cancellation Date: [[ end_date ]]</li><li>Reason: [[ cancellation_reason ]]</li><li>Previous Plan: [[ subscription_plan ]]</li></ul><p>Please review and consider reaching out to understand their feedback.</p>',
        ]);
        $template = NotificationTemplate::create([
            'type' => 'purchase_video',
            'name' => 'purchase_video',
            // Updated display label to match email subject
            'label' => 'One Time Purchase Content',
            'status' => 1,
            'to' => '["admin","demo_admin","user"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1','IS_CUSTOM_WEBHOOK' => '0',],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'You have successfully purchased [[ content_type ]] "[[ name ]]"',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'You have successfully purchased!',
            'notification_subject' => 'Purchase Confirmed',
            'notification_template_detail' => 'Purchase confirmed for [[ user_name ]] - [[ content_type ]] "[[ name ]]" from [[ start_date ]] to [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>Thank you for purchasing the [[ content_type ]] "<strong>[[ name ]]</strong>" on our platform.</p>
                <p>You now have full access starting from <strong>[[ start_date ]] to [[ end_date ]]</strong>.</p>
                <p>We hope you enjoy your viewing experience!</p>
                <p>Best regards,</p>
            ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User [[ user_name ]] has purchased [[ content_type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'You have successfully purchased!',
            'notification_subject' => 'New Purchase - Review Required',
            'notification_template_detail' => 'User [[ user_name ]] purchased [[ content_type ]] "[[ name ]]" on [[ start_date ]] - Access until [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A new video purchase has been made on the platform.</p>
                <p>Purchase Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ content_type ]] "[[ name ]]"</li>
                    <li>Purchase Date: [[ start_date ]]</li>
                    <li>Access Until: [[ end_date ]]</li>
                    <li>Transaction Status: Completed</li>
                </ul>
                <p>Please review the transaction details.</p>
            ',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User [[ user_name ]] has purchased [[ content_type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'You have successfully purchased!',
            'notification_subject' => 'New Purchase - Review Required',
            'notification_template_detail' => 'User [[ user_name ]] purchased [[ content_type ]] "[[ name ]]" on [[ start_date ]] - Access until [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A new video purchase has been made on the platform.</p>
                <p>Purchase Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ content_type ]] "[[ name ]]"</li>
                    <li>Purchase Date: [[ start_date ]]</li>
                    <li>Access Until: [[ end_date ]]</li>
                    <li>Transaction Status: Completed</li>
                </ul>
                <p>Please review the transaction details.</p>
            ',
        ]);
        $template = NotificationTemplate::create([
            'type' => 'rent_video',
            'name' => 'rent_video',
            // Updated display label to match email subject
            'label' => 'Rental Content',
            'status' => 1,
            'to' => '["admin","demo_admin","user"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1','IS_CUSTOM_WEBHOOK' => '0',],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'You have successfully rent [[ content_type ]] "[[ name ]]"',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'You have successfully rent!',
            'notification_subject' => 'Rental Confirmed',
            'notification_template_detail' => 'Rental confirmed for [[ user_name ]] - [[ content_type ]] "[[ name ]]" from [[ start_date ]] to [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>Thank you for renting the [[ content_type ]] "<strong>[[ name ]]</strong>" from our platform.</p>
                <p>Your rental starts on <strong>[[ start_date ]]</strong> and will be available until <strong>[[ end_date ]]</strong>.</p>
                <p>Be sure to complete watching it before your rental expires!</p>

            ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User [[ user_name ]] has rented [[ content_type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'You have successfully rent!',
            'notification_subject' => 'New Rental - Review Required',
            'notification_template_detail' => 'User [[ user_name ]] rented [[ content_type ]] "[[ name ]]" on [[ start_date ]] - Rental until [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A new video rental has been made on the platform.</p>
                <p>Rental Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ content_type ]] "[[ name ]]"</li>
                    <li>Rental Start: [[ start_date ]]</li>
                    <li>Rental End: [[ end_date ]]</li>
                    <li>Rental Status: Active</li>
                </ul>
                <p>Please review the rental transaction details.</p>
            ',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User [[ user_name ]] has rented [[ content_type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'You have successfully rent!',
            'notification_subject' => 'New Rental - Review Required',
            'notification_template_detail' => 'User [[ user_name ]] rented [[ content_type ]] "[[ name ]]" on [[ start_date ]] - Rental until [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A new video rental has been made on the platform.</p>
                <p>Rental Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ content_type ]] "[[ name ]]"</li>
                    <li>Rental Start: [[ start_date ]]</li>
                    <li>Rental End: [[ end_date ]]</li>
                    <li>Rental Status: Active</li>
                </ul>
                <p>Please review the rental transaction details.</p>
            ',
        ]);
        $template = NotificationTemplate::create([
            'type' => 'rent_expiry_reminder',
            'name' => 'rent_expiry_reminder',
            'label' => 'Rental Is Expire Soon',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Reminder: Your access to [[ content_type ]] "[[ name ]]" will expire in [[ end_date ]].',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Rent Is Expire Soon!',
            'notification_subject' => 'Rental Expiry Reminder',
            'notification_template_detail' => 'Rental expiry reminder for [[ user_name ]] - [[ content_type ]] "[[ name ]]" expires on [[ end_date ]]',
            'template_detail' => '
                <p>Hi [[ user_name ]],</p>
                <p>This is a reminder that your rental access to the [[ content_type ]] "<strong>[[ name ]]</strong>" will expire in <strong>[[ end_date ]]</strong>.</p>
                <p>If you haven\'t finished watching it yet, please make sure to complete it before your rental period ends.</p>
                <p>Enjoy your content,<br>
            ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Rental expiry reminder sent to user [[ user_name ]] for [[ content_type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Rent Is Expire Soon!',
            'notification_subject' => 'Rental Expiry Notification Sent',
            'notification_template_detail' => 'Rental expiry reminder sent to [[ user_name ]] for [[ content_type ]] "[[ name ]]" - Expires on [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A rental expiry reminder has been sent to a user.</p>
                <p>Rental Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ content_type ]] "[[ name ]]"</li>
                    <li>Expiry Date: [[ end_date ]]</li>
                    <li>Reminder Status: Sent</li>
                </ul>
                <p>Please monitor the rental status and ensure proper expiry handling.</p>
            ',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Rental expiry reminder sent to user [[ user_name ]] for [[ content_type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Rent Is Expire Soon!',
            'notification_subject' => 'Rental Expiry Notification Sent',
            'notification_template_detail' => 'Rental expiry reminder sent to [[ user_name ]] for [[ content_type ]] "[[ name ]]" - Expires on [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A rental expiry reminder has been sent to a user.</p>
                <p>Rental Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ content_type ]] "[[ name ]]"</li>
                    <li>Expiry Date: [[ end_date ]]</li>
                    <li>Reminder Status: Sent</li>
                </ul>
                <p>Please monitor the rental status and ensure proper expiry handling.</p>
            ',
        ]);
        $template = NotificationTemplate::create([
            'type' => 'purchase_expiry_reminder',
            'name' => 'purchase_expiry_reminder',
            'label' => 'One Time Purchase Is Expired Soon',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Reminder: Your access to purchased [[ type ]] "[[ name ]]" will expire in [[ end_date ]].',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Purchase Is Expire Soon!',
            'notification_subject' => 'Purchase Expiry Reminder',
            'notification_template_detail' => 'Purchase expiry reminder for [[ user_name ]] - [[ type ]] "[[ name ]]" expires on [[ end_date ]]',
            'template_detail' => '
                <p>Hello [[ user_name ]],</p>
                <p>This is a reminder that your access to the purchased [[ type ]] "<strong>[[ name ]]</strong>" will expire in <strong>[[ end_date ]]</strong>.</p>
                <p>Please ensure you complete watching it before your access period ends.</p>
            ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Purchase expiry reminder sent to user [[ user_name ]] for [[ type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Purchase Is Expire Soon!',
            'notification_subject' => 'Purchase Expiry Notification Sent',
            'notification_template_detail' => 'Purchase expiry reminder sent to [[ user_name ]] for [[ type ]] "[[ name ]]" - Expires on [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A purchase expiry reminder has been sent to a user.</p>
                <p>Purchase Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ type ]] "[[ name ]]"</li>
                    <li>Expiry Date: [[ end_date ]]</li>
                    <li>Reminder Status: Sent</li>
                    <li>Access Type: Purchased</li>
                </ul>
                <p>Please monitor the access status and ensure proper expiry handling.</p>
            ',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Purchase expiry reminder sent to user [[ user_name ]] for [[ type ]] "[[ name ]]".',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Purchase Is Expire Soon!',
            'notification_subject' => 'Purchase Expiry Notification Sent',
            'notification_template_detail' => 'Purchase expiry reminder sent to [[ user_name ]] for [[ type ]] "[[ name ]]" - Expires on [[ end_date ]]',
            'template_detail' => '
                <p>Dear [[ user_name ]],</p>
                <p>A purchase expiry reminder has been sent to a user.</p>
                <p>Purchase Details:</p>
                <ul>
                    <li>User: [[ user_name ]]</li>
                    <li>Content: [[ type ]] "[[ name ]]"</li>
                    <li>Expiry Date: [[ end_date ]]</li>
                    <li>Reminder Status: Sent</li>
                    <li>Access Type: Purchased</li>
                </ul>
                <p>Please monitor the access status and ensure proper expiry handling.</p>
            ',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'video_add',
            'name' => 'Video Added',
            'label' => 'Video Added',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '0', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New video "[[ video_name ]]" is now available for streaming!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Video Added!',
            'notification_subject' => 'New Video Available',
            'notification_template_detail' => 'New video "[[ video_name ]]" available - Get ready for hours of entertainment!',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>Exciting news! A new video "<strong>[[ video_name ]]</strong>" is now available for streaming.</p><p>Get ready for hours of entertainment!</p>',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Video "[[ video_name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Video Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Video "[[ video_name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The season "<strong>[[ season_name ]]</strong>" has been successfully added to the content management system.</p><p>Season Details:</p><ul><li>Season Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the season details and episode structure.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Video "[[ name ]]" has been successfully added to the system.',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Video Added!',
            'notification_subject' => 'Content Added - Review Required',
            'notification_template_detail' => 'Video "[[ name ]]" added by [[ logged_in_user_fullname ]] - Content management update.',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>The video "<strong>[[ name ]]</strong>" has been successfully added to the content management system.</p><p>Video Details:</p><ul><li>Video Name: [[ name ]]</li><li>Added By: [[ logged_in_user_fullname ]]</li><li>Status: Active</li><li>Available for streaming</li></ul><p>Please review the video details and episode structure.</p>',
        ]);

        $template = NotificationTemplate::create([
            'type' => 'subscription_expiry_reminder',
            'name' => 'subscription_expiry_reminder',
            'label' => 'Subscription Expiry Reminder',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your subscription plan "[[ subscription_plan ]]" is expiring soon. Please renew to continue enjoying our services.',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Subscription Expiring Soon!',
            'notification_subject' => 'Subscription Expiry Reminder',
            'notification_template_detail' => 'Your subscription "[[ subscription_plan ]]" expires on [[ end_date ]]. Renew now to continue enjoying our services.',
            'template_detail' => '
                <p>Hello [[ user_name ]],</p>
                <p>Your subscription plan "<strong>[[ subscription_plan ]]</strong>" is expiring soon. Please renew your subscription plan within the next few days to continue enjoying our services.</p>
                <p><strong>Subscription Details:</strong></p>
                <ul>
                    <li>Plan: [[ subscription_plan ]]</li>
                    <li>Expiry Date: [[ end_date ]]</li>
                    <li>Amount: [[ amount_formatted ]]</li>
                </ul>
                <p>Don\'t miss out on your favorite content! Renew now to continue streaming.</p>
                <p>Thank you for being a valued member!</p>
            ',
        ]);

        // Expiry Plan Notification Template
        $template = NotificationTemplate::create([
            'type' => 'expiry_plan',
            'name' => 'expiry_plan',
            'label' => 'Expiry Plan',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your subscription plan "[[ subscription_plan ]]" will expire in [[ days ]] day(s). Please renew to continue enjoying our services.',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Subscription Plan Expiring Soon!',
            'notification_subject' => 'Subscription Plan Expiry Reminder',
            'notification_template_detail' => 'Your subscription plan "[[ subscription_plan ]]" will expire in [[ days ]] day(s). Expiry date: [[ end_date ]].',
            'template_detail' => '
                <p>Hello [[ user_name ]],</p>
                <p>This is a reminder that your subscription plan "<strong>[[ subscription_plan ]]</strong>" will expire in <strong>[[ days ]]</strong> day(s).</p>
                <p><strong>Subscription Details:</strong></p>
                <ul>
                    <li>Plan: [[ subscription_plan ]]</li>
                    <li>Expiry Date: [[ end_date ]]</li>
                    <li>Days Remaining: [[ days ]]</li>
                    <li>Amount: [[ amount_formatted ]]</li>
                </ul>
                <p>Don\'t miss out on your favorite content! Renew now to continue streaming without interruption.</p>
                <p>Thank you for being a valued member!</p>
            ',
        ]);

        // Upcoming Notification Template
        $template = NotificationTemplate::create([
            'type' => 'upcoming',
            'name' => 'upcoming',
            'label' => 'Upcoming',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Great news! "[[ name ]]" is releasing in [[ days ]] day(s). Don\'t miss it!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Upcoming Release: [[ name ]]',
            'notification_subject' => 'Upcoming Content Release',
            'notification_template_detail' => 'Upcoming release: "[[ name ]]" will be available in [[ days ]] day(s) on [[ release_date ]].',
            'template_detail' => '
                <p>Hello [[ user_name ]],</p>
                <p>Great news! "<strong>[[ name ]]</strong>" is releasing in <strong>[[ days ]]</strong> day(s).</p>
                <p><strong>Content Details:</strong></p>
                <ul>
                    <li>Title: [[ name ]]</li>
                    <li>Type: [[ content_type ]]</li>
                    <li>Release Date: [[ release_date ]]</li>
                    <li>Days Until Release: [[ days ]]</li>
                </ul>
                <p>Don\'t miss out on this exciting new content! Set a reminder and be the first to watch.</p>
                <p>We can\'t wait for you to enjoy it!</p>
            ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Upcoming release: "[[ name ]]" will be launching in [[ days ]] day(s).',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Upcoming Release Detected',
            'notification_subject' => 'Upcoming Content Alert',
            'notification_template_detail' => 'Upcoming content "[[ name ]]" (Type: [[ content_type ]]) releasing on [[ release_date ]].',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>This is a notification about an upcoming content release.</p><ul><li>Title: <strong>[[ name ]]</strong></li><li>Type: [[ content_type ]]</li><li>Release Date: [[ release_date ]]</li><li>Days Remaining: [[ days ]]</li></ul><p>Please ensure all metadata and assets are ready.</p>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Upcoming release: "[[ name ]]" will be launching in [[ days ]] day(s).',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Upcoming Release Detected',
            'notification_subject' => 'Upcoming Content Alert',
            'notification_template_detail' => 'Upcoming content "[[ name ]]" (Type: [[ content_type ]]) releasing on [[ release_date ]].',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>This is a notification about an upcoming content release.</p><ul><li>Title: <strong>[[ name ]]</strong></li><li>Type: [[ content_type ]]</li><li>Release Date: [[ release_date ]]</li><li>Days Remaining: [[ days ]]</li></ul><p>Please ensure all metadata and assets are ready.</p>',
        ]);

        // Continue Watch Notification Template
        $template = NotificationTemplate::create([
            'type' => 'continue_watch',
            'name' => 'continue_watch',
            'label' => 'Continue Watch',
            'status' => 1,
            'to' => '["user","admin","demo_admin"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '1', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Continue watching "[[ name ]]". Pick up where you left off!',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'Continue Watching: [[ name ]]',
            'notification_subject' => 'Continue Watching Reminder',
            'notification_template_detail' => 'Continue watching "[[ name ]]". You haven\'t watched this in a while - pick up where you left off!',
            'template_detail' => '
                <p>Hello [[ user_name ]],</p>
                <p>You haven\'t watched "<strong>[[ name ]]</strong>" in a while. Pick up where you left off!</p>
                <p><strong>Content Details:</strong></p>
                <ul>
                    <li>Title: [[ name ]]</li>
                    <li>Type: [[ content_type ]]</li>
                    <li>Release Date: [[ release_date ]]</li>
                </ul>
                <p>Continue your viewing experience and enjoy the rest of the content!</p>
                <p>Happy streaming!</p>
            ',
        ]);

        // Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User reminder: Continue watching "[[ name ]]".',
            'status' => 1,
            'user_type' => 'admin',
            'subject' => 'Continue Watch Reminder Sent',
            'notification_subject' => 'Continue Watch - User Notification',
            'notification_template_detail' => 'Continue watch reminder for "[[ name ]]".',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>A continue watching reminder for "<strong>[[ name ]]</strong>" has been processed.</p><p>Content Details:</p><ul><li>Title: [[ name ]]</li><li>Type: [[ content_type ]]</li></ul>',
        ]);

        // Demo Admin type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'User reminder: Continue watching "[[ name ]]".',
            'status' => 1,
            'user_type' => 'demo_admin',
            'subject' => 'Continue Watch Reminder Sent',
            'notification_subject' => 'Continue Watch - User Notification',
            'notification_template_detail' => 'Continue watch reminder for "[[ name ]]".',
            'template_detail' => '<p>Dear [[ user_name ]],</p><p>A continue watching reminder for "<strong>[[ name ]]</strong>" has been processed.</p><p>Content Details:</p><ul><li>Title: [[ name ]]</li><li>Type: [[ content_type ]]</li></ul>',
        ]);

        // Parental Control OTP Notification Template
        $template = NotificationTemplate::create([
            'type' => 'parental_control_otp',
            'name' => 'parental_control_otp',
            'label' => 'Parental Control OTP',
            'status' => 1,
            'to' => '["user"]',
            'channels' => ['IS_MAIL' => '1', 'PUSH_NOTIFICATION' => '0', 'IS_CUSTOM_WEBHOOK' => '0'],
        ]);

        // User type notification template mapping
        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your OTP for parental control PIN change is [[ otp ]].',
            'status' => 1,
            'user_type' => 'user',
            'subject' => 'OTP Verification',
            'notification_subject' => 'OTP Verification',
            'notification_template_detail' => 'Your OTP for parental control PIN change is [[ otp ]].',
            'template_detail' => '
                <p>Hello [[ user_name ]],</p>
                <p>We have sent a one-time password (OTP) to your registered email. Use the code below to verify your request. Do not share this code with anyone.</p>
                <p><strong>One-Time Password (OTP)</strong></p>
                <p style="font-size: 24px; font-weight: bold; letter-spacing: 4px; text-align: center; padding: 20px; background-color: #f5f5f5; border-radius: 5px;">[[ otp ]]</p>
                <p>This OTP is valid for a limited time. If you did not request this OTP, please ignore this email.</p>
            ',
        ]);

    }
}
