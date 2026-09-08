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
        // Add season_number column if it doesn't exist
        if (!Schema::hasColumn('seasons', 'season_number')) {
            Schema::table('seasons', function (Blueprint $table) {
                $table->integer('season_number')->nullable();
            });
        }

        // Get all entertainments (TV shows) that have seasons
        $entertainmentIds = DB::table('seasons')
            ->whereNotNull('entertainment_id')
            ->distinct()
            ->pluck('entertainment_id');

        foreach ($entertainmentIds as $entertainmentId) {
            // Order seasons by creation time (then id) so numbering follows creation
            $seasons = DB::table('seasons')
                ->where('entertainment_id', $entertainmentId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'season_number']);

            $counter = 1;
            foreach ($seasons as $season) {
                // Only populate when season_number is missing/null
                if (is_null($season->season_number)) {
                    DB::table('seasons')
                        ->where('id', $season->id)
                        ->update(['season_number' => $counter]);
                }
                $counter++;
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         if (Schema::hasColumn('seasons', 'season_number')) {
            Schema::table('seasons', function (Blueprint $table) {
                $table->dropColumn('season_number');
            });
        }
    }
};
