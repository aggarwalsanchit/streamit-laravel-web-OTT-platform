<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Frontend\Models\PayPerView;
use Modules\Season\Models\Season;
use Modules\Video\Models\Video;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;

class SendPayPerViewExpiryNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send-pay-per-view-expiry-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Pay-Per-View expiry reminder for items expiring within the next 3 days';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $threeDaysFromNow = $today->copy()->addDays(3)->endOfDay();

        $this->info("Checking for Pay-Per-View items expiring between {$today->toDateString()} and {$threeDaysFromNow->toDateString()}...");

        $ppvs = PayPerView::whereBetween('view_expiry_date', [$today, $threeDaysFromNow])->get();

        if ($ppvs->isEmpty()) {
            $this->info("No expiring Pay-Per-View items found in this range.");
            return;
        }

        $sentCount = 0;

        foreach ($ppvs as $ppv) {
            $user = User::find($ppv->user_id);
            $movie = null;

            switch ($ppv->type) {
                case 'movie':
                case 'tvshow':
                    $movie = Entertainment::find($ppv->movie_id);
                    break;
                case 'video':
                    $movie = Video::find($ppv->movie_id);
                    break;
                case 'episode':
                    $movie = Episode::find($ppv->movie_id);
                    break;
                case 'season':
                    $movie = Season::find($ppv->movie_id);
                    break;
            }

            if (!$user || !$movie) continue;

            $notificationType = (isset($movie->purchase_type) && $movie->purchase_type == 'rental') ? 'rent_expiry_reminder' : 'purchase_expiry_reminder';

            // Check if notification was already sent today to avoid duplicates
            // We search for a notification for this user, this specific PPV ID, and this specific type
            $alreadySent = \App\Models\Notification::where('notifiable_id', $user->id)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", [$notificationType])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.id')) = ?", [(string) $ppv->id])
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySent) {
                $this->info("Skipping {$notificationType} for User: {$user->name} (Content: {$movie->name}) - Already sent today.");
                continue;
            }

            sendNotification([
                'notification_type' => $notificationType ?? null,
                'id' => $ppv->id ?? null,
                'user_id' => $user->id ?? null,
                'user_name' => $user->full_name ?? null,
                'name' => $movie->name ?? 'Your Content',
                'content_type' => $ppv->type ?? null,
                'notification_group' => 'pay_per_view',
                'start_date' => $ppv->created_at->toDateString() ?? null,
                'end_date' => Carbon::parse($ppv->view_expiry_date)->toDateString() ?? null,
                'amount' => $ppv->price ?? null,
                'transaction_id' => $ppv->PayperviewTransaction->transaction_id ?? null,
                'payment_type' => $ppv->PayperviewTransaction->payment_type ?? null,
                'payment_status' => $ppv->PayperviewTransaction->payment_status ?? null,
            ]);

            $this->info("Sent {$notificationType} to User: {$user->name} for Content: {$movie->name} (Expires: {$ppv->view_expiry_date})");
            $sentCount++;
        }

        $this->info("Total notifications sent: {$sentCount}");
    }
}
