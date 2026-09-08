<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Entertainment\Models\UserReminder;
use Modules\Entertainment\Models\Entertainment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReminderNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send upcoming content release notifications to all users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $upcoming_raw = setting('upcoming');
        $days = intVal($upcoming_raw ?? 1);
      
        $today = Carbon::now()->startOfDay();
        $thresholdDate = $today->copy()->addDays($days)->endOfDay();
        
        $this->info("Checking for content releasing between: {$today->format('Y-m-d')} and {$thresholdDate->format('Y-m-d')} (within {$days} days)");
      

        // Find all movies/TV shows releasing within the configured days (future releases only)
        $upcomingContent = Entertainment::where('status', 1)
            ->whereNull('deleted_at')
            ->whereDate('release_date', '>', $today->format('Y-m-d')) // Future releases only
            ->whereDate('release_date', '<=', $thresholdDate->format('Y-m-d')) // Within configured days
            ->whereIn('type', ['movie', 'tv_show'])
            ->get();

        $notificationCount = 0;
        $userCount = User::where('status', 1)->where('user_type', 'user')->count();
        $today = Carbon::today();

        foreach ($upcomingContent as $entertainment) {
            try {
                // Use cache with lock to prevent race conditions
                $cacheKey = "upcoming_notification_sent_{$entertainment->id}_" . $today->format('Y-m-d');
                
                // Check cache first (fastest check)
                if (Cache::has($cacheKey)) {
                    $this->info("Skipping '{$entertainment->name}' - notification already processed today (cache)");
                    continue;
                }

                // Check database (more reliable)
                $alreadySentToday = \App\Models\Notification::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", ['upcoming'])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.id')) = ?", [(string)$entertainment->id])
                    ->whereDate('created_at', $today)
                    ->exists();

                if ($alreadySentToday) {
                    // Set cache even if found in DB to speed up future checks
                    Cache::put($cacheKey, true, now()->endOfDay());
                    $this->info("Skipping '{$entertainment->name}' - notification already sent today");
                    continue;
                }

                // Check queue for pending jobs - count how many are already queued for this content
                // Since we send to all users, if we see jobs >= userCount, it's already been queued
                $pendingJobsCount = \Illuminate\Support\Facades\DB::table('jobs')
                    ->where('queue', 'notifications')
                    ->where('payload', 'like', '%upcoming%')
                    ->where(function($q) use ($entertainment) {
                        // Check for the entertainment ID in serialized format
                        $q->where('payload', 'like', '%' . $entertainment->id . '%');
                    })
                    ->count();
                
                // If we already have jobs queued for all users (or close to it), skip
                // Allow some margin (80% of users) to account for partial processing
                $minJobsThreshold = max(1, (int)($userCount * 0.8));
                $pendingJobs = $pendingJobsCount >= $minJobsThreshold;

                if ($pendingJobs) {
                    Cache::put($cacheKey, true, now()->endOfDay());
                    $this->info("Skipping '{$entertainment->name}' - notification already queued today");
                    continue;
                }

                // Set cache IMMEDIATELY before sending to prevent race conditions
                Cache::put($cacheKey, true, now()->endOfDay());

                // Calculate actual days remaining until release date
                $releaseDate = Carbon::parse($entertainment->release_date);
                $daysRemaining = $today->diffInDays($releaseDate, false); // false = don't include end date
                
                // Ensure days remaining is positive
                if ($daysRemaining < 0) {
                    $daysRemaining = 0;
                }

                // Send notification using the dynamic template system
                // Since 'upcoming' is in the bulk list, sendNotification will send to all users automatically
           
                sendNotification([
                    'notification_type' => 'upcoming',
                    'id' => $entertainment->id ?? null,
                    'name' => $entertainment->name ?? null,
                    'content_type' => $entertainment->type ?? 'movie',
                    'posterimage' => $entertainment->poster_url ?? null,
                    'release_date' => $entertainment->release_date ? Carbon::parse($entertainment->release_date)->format('Y-m-d') : null,
                    'description' => $entertainment->description ?? null,
                    'days' => $daysRemaining, // Actual days remaining, not configured threshold
                    'days_remaining' => $daysRemaining,
                ]);

                $notificationCount += $userCount;
                $this->info("Queued upcoming notification for '{$entertainment->name}' to {$userCount} users");
                
            } catch (\Exception $e) {
                $this->error("Failed to send notification for entertainment {$entertainment->id}: " . $e->getMessage());
                continue;
            }
        }

        // Also handle manual reminders (for backward compatibility)
        // But skip if we already sent bulk notifications above
        $reminder_data = UserReminder::with('entertainment')
            ->whereDate('release_date', '>', $today->format('Y-m-d'))
            ->whereDate('release_date', '<=', $thresholdDate->format('Y-m-d'))
            ->where('is_remind', 1)
            ->get();

        foreach ($reminder_data as $reminder) {
            $user = User::where('id', $reminder->user_id)->first();
            if (!$user) {
                continue;
            }
            
            $entertainment = $reminder->entertainment;
            if (!$entertainment) {
                continue;
            }

            // Check if we already sent bulk notification for this content (from above)
            $cacheKey = "upcoming_notification_sent_{$entertainment->id}_" . $today->format('Y-m-d');
            $alreadySentBulk = Cache::has($cacheKey);
            
            // Also check database
            $alreadySentToday = \App\Models\Notification::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", ['upcoming'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.id')) = ?", [(string)$entertainment->id])
                ->whereDate('created_at', $today)
                ->exists();

            // Skip if already sent via bulk notification (which sends to all users anyway)
            if ($alreadySentBulk || $alreadySentToday) {
                continue;
            }

            // Calculate actual days remaining until release date
            $releaseDate = Carbon::parse($entertainment->release_date);
            $daysRemaining = $today->diffInDays($releaseDate, false); // false = don't include end date
            
            // Ensure days remaining is positive
            if ($daysRemaining < 0) {
                $daysRemaining = 0;
            }

            // For manual reminders, send only to that specific user
            sendNotification([
                'notification_type' => 'upcoming',
                'user_id' => $reminder->user_id,
                'id' => $entertainment->id ?? null,
                'user_name' => $user->full_name ?? $user->name ?? '',
                'name' => $entertainment->name ?? null,
                'content_type' => $entertainment->type ?? 'movie',
                'posterimage' => $entertainment->poster_url ?? null,
                'release_date' => $entertainment->release_date ? Carbon::parse($entertainment->release_date)->format('Y-m-d') : null,
                'description' => $entertainment->description ?? null,
                'days' => $daysRemaining, // Actual days remaining, not configured threshold
                'days_remaining' => $daysRemaining,
            ]);

            $notificationCount++;
        }

        $this->info("Upcoming notifications sent successfully. Total: {$notificationCount} notifications sent.");
       
        return 0;
    }
}
