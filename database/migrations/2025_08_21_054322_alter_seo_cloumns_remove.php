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
         // Drop slug column from entertainments table
         Schema::table('entertainments', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        // Drop slug column from seasons table
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        // Drop slug column from episodes table
        Schema::table('episodes', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        // Drop slug column from videos table
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn('slug');
        });

        Schema::table('seo', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
