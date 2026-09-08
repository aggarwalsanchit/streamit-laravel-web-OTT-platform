<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fill missing episode_number values per season based on creation order.
     */
    public function up(): void
    {
        // Get all seasons that have episodes
        $seasonIds = DB::table('episodes')
            ->whereNotNull('season_id')
            ->distinct()
            ->pluck('season_id');

        foreach ($seasonIds as $seasonId) {
            // Order episodes by creation time (then id) so numbering follows creation
            $episodes = DB::table('episodes')
                ->where('season_id', $seasonId)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'episode_number']);

            $counter = 1;
            foreach ($episodes as $episode) {
                // Only populate when episode_number is missing/null
                if (is_null($episode->episode_number)) {
                    DB::table('episodes')
                        ->where('id', $episode->id)
                        ->update(['episode_number' => $counter]);
                }
                $counter++;
            }
        }
    }

    /**
     * No reliable down action since original values are unknown.
     */
    public function down(): void
    {
        // Intentionally left blank
    }
};


