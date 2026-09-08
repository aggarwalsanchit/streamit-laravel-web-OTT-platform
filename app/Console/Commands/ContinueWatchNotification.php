<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Entertainment\Models\ContinueWatch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ContinueWatchNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'continuewatch:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $continue_watch_raw = setting('continue_watch');
        $days = intVal($continue_watch_raw);
        
        $expiryThreshold = Carbon::now()->subDays($days);
        $continuewatch_data = ContinueWatch::with('entertainment','episode','video')
            ->where('updated_at', '<=', $expiryThreshold)
            ->whereRaw('watched_time < total_watched_time')
            ->get();

        $today = Carbon::today();
        $notificationCount = 0;

        foreach ($continuewatch_data as $continuewatch) {
            $user = User::where('id', $continuewatch->user_id)->first();
            if (!$user) {
                continue;
            }

            $entertainment = null;
            $contentType = 'movie';
            if($continuewatch->entertainment_type == 'movie'){
                $entertainment = $continuewatch->entertainment;
                $contentType = 'movie';
            }
            else if($continuewatch->entertainment_type == 'episode'){
                $entertainment = $continuewatch->episode;
                $contentType = 'episode';
            }
            else if($continuewatch->entertainment_type == 'video'){
                $entertainment = $continuewatch->video;
                $contentType = 'video';
            }
            else if($continuewatch->entertainment_type == 'tvshow'){
                $entertainment = $continuewatch->episode;
                $contentType = 'episode'; // Treat tvshow continue watch as episode notification if it has episode data
            }

            if (!$entertainment) {
                continue;
            }

            // Check if notification was already sent today for this content (to any user)
            // Since continue_watch sends to all users, check if it was sent for this content today
            $cacheKey = "continue_watch_notification_sent_{$entertainment->id}_{$user->id}_" . $today->format('Y-m-d');
            
            $alreadySentToday = \App\Models\Notification::whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", ['continue_watch'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.id')) = ?", [(string)$entertainment->id])
                ->where('notifiable_id', $user->id)
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySentToday || Cache::has($cacheKey)) {
                continue; // Skip if already sent today for this content
            }

            // Set cache immediately to prevent duplicate sends
            Cache::put($cacheKey, true, now()->endOfDay());

            try {
                // Send notification using the dynamic template system
                // Since 'continue_watch' is in the bulk list, sendNotification will send to all users automatically
                
                sendNotification([
                    'notification_type' => 'continue_watch',
                    'id' => $entertainment->id ?? null,
                    'name' => $entertainment->name ?? null,
                    'content_type' => $contentType,
                    'posterimage' => $entertainment->poster_url ?? null,
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'release_date' => $entertainment->release_date ? Carbon::parse($entertainment->release_date)->format('Y-m-d') : null,
                    'description' => $entertainment->description ?? null,
                ]);

                $userCount = User::where('status', 1)->where('user_type', 'user')->count();
                $notificationCount += $userCount;
                $this->info("Queued continue watch notification for '{$entertainment->name}' to {$userCount} users");
                
            } catch (\Exception $e) {
                $this->error("Failed to send notification for content {$entertainment->id}: " . $e->getMessage());
                continue;
            }
        }

        // Process queue immediately to send notifications
        if ($notificationCount > 0) {
            $this->info("Processing notification queue...");
           
            $result = Artisan::call('queue:work', [
                '--queue' => 'notifications',
                '--tries' => 3,
                '--stop-when-empty' => true,
                '--timeout' => 300
            ]);
           
        } else {
           
        }

        $this->info("Continue Watch notifications sent successfully. Total: {$notificationCount} notifications queued.");
      
        return 0;
    }
}
