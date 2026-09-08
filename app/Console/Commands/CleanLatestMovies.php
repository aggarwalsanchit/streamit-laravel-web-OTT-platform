<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MobileSetting;
use Modules\Entertainment\Models\Entertainment;
use Carbon\Carbon;

class CleanLatestMovies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'streamit:clean-latest-movies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove movies older than 1 year from the latest-movies mobile setting';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $setting = MobileSetting::where('slug', 'latest-movies')->first();

        if (!$setting || empty($setting->value)) {
            $this->info('Latest movies setting not found or empty.');
            return 0;
        }

        $currentIds = json_decode($setting->value, true);

        if (!is_array($currentIds) || empty($currentIds)) {
             $this->info('No movies configured in latest movies.');
             return 0;
        }

        // Fetch IDs of movies that are considered valid "latest" movies
        // Criteria: ID is in the current list AND Release Date is within the last year
        $validMovieIds = Entertainment::whereIn('id', $currentIds)
            ->whereDate('release_date', '>=', Carbon::now()->subYear())
            ->pluck('id')
            ->toArray();
        
        // Filter the current list to keep only valid IDs, preserving order
        $newIds = array_values(array_filter($currentIds, function($id) use ($validMovieIds) {
            return in_array($id, $validMovieIds);
        }));

        if (count($currentIds) !== count($newIds)) {
            $removedCount = count($currentIds) - count($newIds);
            
            $setting->value = json_encode($newIds); // Re-encode as array
            $setting->save();
            
            $this->info("Successfully removed {$removedCount} old movies from the latest-movies list.");
        } else {
             $this->info('No movies needed removal. All movies are within the 1-year range.');
        }

        return 0;
    }
}
