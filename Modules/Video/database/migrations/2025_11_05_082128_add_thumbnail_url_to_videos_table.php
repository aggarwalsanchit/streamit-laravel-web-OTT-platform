<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('videos', 'thumbnail_url')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->text('thumbnail_url')->nullable()->after('trailer_url'); 
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('videos', 'thumbnail_url')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->dropColumn('thumbnail_url');
            });
        }
    }
};
