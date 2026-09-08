<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Subscriptions\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Currency;

class SendSubscriptionNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:notify';

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
        $expiry_days_raw = setting('expiry_plan');
        $expiry_days = intVal($expiry_days_raw) ?? 7;
        Log::info("SendSubscriptionNotifications: expiry_plan setting value={$expiry_days_raw}, parsed={$expiry_days}");
        $today = Carbon::today();
        $expiryThreshold = $today->copy()->addDays($expiry_days)->endOfDay();
        
        $this->info("Checking for subscriptions expiring between: {$today->format('Y-m-d')} and {$expiryThreshold->format('Y-m-d')} (within {$expiry_days} days)");
        Log::info("SendSubscriptionNotifications: Checking subscriptions expiring between {$today->format('Y-m-d')} and {$expiryThreshold->format('Y-m-d')} (within {$expiry_days} days)");

        // Find subscriptions that:
        // 1. Are still active
        // 2. Haven't expired yet (end_date >= today)
        // 3. Are expiring within the configured days (end_date <= today + expiry_days)
        $subscriptions = Subscription::with('user', 'plan')
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today->format('Y-m-d')) // Not expired yet
            ->whereDate('end_date', '<=', $expiryThreshold->format('Y-m-d')) // Expiring within configured days
            ->get();

        $notificationCount = 0;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            if (!$user || $user->status != 1) {
                continue;
            }

            // Calculate actual days remaining until expiry
            $endDate = Carbon::parse($subscription->end_date);
            $daysRemaining = (int) ceil($today->diffInDays($endDate, false)); // false = don't include end date
            
            // Only send if subscription is expiring within the configured days
            if ($daysRemaining < 0 || $daysRemaining > $expiry_days) {
                continue;
            }

            // Check if notification was already sent today for this subscription
            $cacheKey = "expiry_plan_notification_sent_{$subscription->id}_{$user->id}_" . $today->format('Y-m-d');
            
            $alreadySentToday = \App\Models\Notification::where('notifiable_id', $user->id)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.notification_type')) = ?", ['expiry_plan'])
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.data.id')) = ?", [(string)$subscription->id])
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadySentToday || Cache::has($cacheKey)) {
                $this->info("Skipping subscription {$subscription->id} for user {$user->id} - notification already sent today");
                continue; // Skip if already sent today
            }

            // Set cache immediately to prevent duplicate sends
            Cache::put($cacheKey, true, now()->endOfDay());

            // Prepare subscription data for notification
            // Note: helpers.php expects 'subscription' key with 'name' field
            $subscriptionData = [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'name' => $subscription->name ?? optional($subscription->plan)->name ?? 'Subscription', // Required by helpers.php
                'subscription_plan' => $subscription->name ?? optional($subscription->plan)->name ?? 'Subscription',
                'identifier' => $subscription->identifier,
                'type' => $subscription->type,
                'status' => $subscription->status,
                'amount' => $subscription->amount,
                'tax_amount' => $subscription->tax_amount ?? 0,
                'total_amount' => $subscription->total_amount ?? $subscription->amount,
                'duration' => $subscription->duration ?? '',
                'plan_type' => $subscription->plan_type ?? '',
                'user' => $user,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'days' => $daysRemaining, // Actual days remaining, not configured days
                'days_remaining' => $daysRemaining,
            ];

            // Send notification using the dynamic template system
            // Pass days directly in the notification data for placeholder replacement
            Log::info("SendSubscriptionNotifications: Sending notification for subscription {$subscription->id} to user {$user->id}, expires in {$daysRemaining} days");
            sendNotification([
                'notification_type' => 'expiry_plan',
                'user_id' => $user->id,
                'id' => $subscription->id, // Add id for duplicate prevention
                'days' => (int) ceil($daysRemaining), // Pass days directly for placeholder replacement
                'days_remaining' => (int) ceil($daysRemaining),
                'subscription' => $subscriptionData,
            ]);

            $notificationCount++;
            $this->info("Queued expiry notification for subscription {$subscription->id} (user: {$user->id}, expires in {$daysRemaining} days)");
            Log::info("SendSubscriptionNotifications: Queued notification for subscription {$subscription->id}, total queued so far: {$notificationCount}");
        }

        // Process queue immediately to send notifications
        if ($notificationCount > 0) {
            $this->info("Processing notification queue...");
            Log::info("SendSubscriptionNotifications: Processing queue for {$notificationCount} notifications");
            $result = Artisan::call('queue:work', [
                '--queue' => 'notifications',
                '--tries' => 3,
                '--stop-when-empty' => true,
                '--timeout' => 300
            ]);
            Log::info("SendSubscriptionNotifications: Queue processing completed with exit code: {$result}");
        } else {
            Log::info("SendSubscriptionNotifications: No notifications to process (notificationCount=0)");
        }

        $this->info("Subscription expiry notifications sent successfully. Total: {$notificationCount} notifications queued.");
        Log::info("SendSubscriptionNotifications: Command completed. Total notifications: {$notificationCount}");
        return 0;
    }
}
