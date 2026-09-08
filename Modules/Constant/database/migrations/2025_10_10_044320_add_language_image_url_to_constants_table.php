<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('constants', function (Blueprint $table) {
            $table->string('language_image')->nullable()->after('value');
        });
        
        Artisan::call('db:seed', [
            '--class' => 'Modules\\Constant\\database\\seeders\\LanguageImageSeeder',
            '--force' => true,
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('constants', function (Blueprint $table) {
            $table->dropColumn('language_image');
        });
    }
};
