<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_template_content_mapping', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_template_content_mapping', 'user_type')) {
                $table->string('user_type')->nullable()->after('language');
            }
            if (!Schema::hasColumn('notification_template_content_mapping', 'notification_subject')) {
                $table->string('notification_subject')->nullable()->after('subject');
            }
            if (!Schema::hasColumn('notification_template_content_mapping', 'notification_template_detail')) {
                $table->longText('notification_template_detail')->nullable()->after('notification_subject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notification_template_content_mapping', function (Blueprint $table) {
            if (Schema::hasColumn('notification_template_content_mapping', 'notification_template_detail')) {
                $table->dropColumn('notification_template_detail');
            }
            if (Schema::hasColumn('notification_template_content_mapping', 'notification_subject')) {
                $table->dropColumn('notification_subject');
            }
            if (Schema::hasColumn('notification_template_content_mapping', 'user_type')) {
                $table->dropColumn('user_type');
            }
        });
    }
};
