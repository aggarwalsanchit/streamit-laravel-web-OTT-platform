<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Models\EntertainmentView;
use Modules\Subscriptions\Models\SubscriptionTransactions;
use Modules\Entertainment\Models\Entertainment;
use Modules\Video\Models\Video;
use Modules\Subscriptions\Models\Plan;
use Modules\Subscriptions\Models\Subscription;
use Modules\Genres\Models\Genres;
use Modules\Entertainment\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Frontend\Models\PayperviewTransaction;
use Modules\Episode\Models\Episode;
use Illuminate\Support\Facades\Storage;


class BackendController extends Controller
{
    /**
     * Get localized plan name
     *
     * @param string $planName
     * @param string $planIdentifier
     * @return string
     */
    private function getLocalizedPlanName($planName, $planIdentifier = null)
    {
        // Map common plan identifiers to translation keys
        $planTranslations = [
            'basic' => __('plan.basic'),
            'premium_plan' => __('plan.premium_plan'),
            'premium' => __('plan.premium_plan'),
            'ultimate_plan' => __('plan.ultimate_plan'),
            'ultimate' => __('plan.ultimate_plan'),
            'elite_plan' => __('plan.elite_plan'),
            'elite' => __('plan.elite_plan'),
        ];

        // Try to get translation by identifier first
        if ($planIdentifier && isset($planTranslations[strtolower($planIdentifier)])) {
            return $planTranslations[strtolower($planIdentifier)];
        }

        // Try to match by plan name (case-insensitive)
        $lowerName = strtolower($planName);
        foreach ($planTranslations as $key => $translation) {
            if (strpos($lowerName, $key) !== false) {
                return $translation;
            }
        }

        // Return original name if no translation found
        return $planName;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      
        $defaultFormat = Setting::where('name', 'default_date_format')->where('datatype', 'misc')->value('val')  ?? 'Y-m-d';

        [$startDate, $endDate] = $this->parseDateRange($request->get('date_range'));

        $dateRange = $request->get('date_range');

        // Set default date range for display if none provided
        if (!$dateRange) {
            $dateRange = formatDate(Carbon::now()->startOfYear()->format('Y-m-d')) . ' to ' . formatDate(Carbon::now()->format('Y-m-d'));
        } else {
            // Format the date range using the default format for display
            $dates = explode(' to ', $dateRange);
            if (count($dates) == 2) {
                $dateRange = formatDate(trim($dates[0])) . ' to ' . formatDate(trim($dates[1]));
            } elseif (count($dates) == 1) {
                $dateRange = formatDate(trim($dates[0]));
            }
        }

        [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->resolveComparisonPeriods($startDate, $endDate);

        // Optimize: Cache frequently used values
        $startOfMonth = Carbon::now()->startOfMonth();
        $currentDate = Carbon::now();
        $expiryThreshold = $currentDate->copy()->addDays(7);

        // Optimize: Use single query for all user counts
        $userCounts = $this->getOptimizedUserCounts($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd, $startOfMonth);

        // Optimize: Use single query for basic counts
        $basicCounts = $this->getBasicCounts();

        // Optimize: Use single query for entertainment data
    
        $entertainmentData = $this->getEntertainmentData();
        // Optimize: Use single query for subscription data
        $subscriptionData = $this->getSubscriptionData($startDate, $endDate);

        // Optimize: Use single query for revenue data
        $revenueData = $this->getRevenueData($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd);

        // Optimize: Use single query for rent content data
        $rentContentData = $this->getRentContentData($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd);

        // Optimize: Use single query for review data
        $reviewData = $this->getReviewData($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd);

        // Optimize: Use single query for transactions
        $transactions = SubscriptionTransactions::orderBy('created_at', 'desc')->take(4)->get();
        
        // Optimize: Use single query for most viewed entertainments
        $entertainments = $this->getMostViewedEntertainments();
        
        // Extract values from optimized queries
        $allUsers = $userCounts['allUsers'];
        $newUsersCount = $userCounts['newUsersCount'];
        $totalusers = $userCounts['totalusers'];
        $usersCurrent = $userCounts['usersCurrent'];
        $usersPrevious = $userCounts['usersPrevious'];
        $usersChangePercent = $userCounts['usersChangePercent'];
        $usersChangeUp = $userCounts['usersChangeUp'];
        $usersTrend = $userCounts['usersTrend'];
        $usersTrendDates = $userCounts['usersTrendDates'];
        $activeusers = $userCounts['activeusers'];
        $totalSubscribers = $userCounts['totalSubscribers'];
        $subsCurrent = $userCounts['subsCurrent'];
        $subsPrevious = $userCounts['subsPrevious'];
        $subsChangePercent = $userCounts['subsChangePercent'];
        $subsChangeUp = $userCounts['subsChangeUp'];
        $subsTrend = $userCounts['subsTrend'];
        $subsTrendDates = $userCounts['subsTrendDates'];
        $totalsoontoexpire = $userCounts['totalsoontoexpire'];
        $soonExpireCurrent = $userCounts['soonExpireCurrent'];
        $soonExpirePrevious = $userCounts['soonExpirePrevious'];
        $soonExpireChangePercent = $userCounts['soonExpireChangePercent'];
        $soonExpireChangeUp = $userCounts['soonExpireChangeUp'];
        $soonExpireTrend = $userCounts['soonExpireTrend'];
        $soonExpireTrendDates = $userCounts['soonExpireTrendDates'];

        $totalDownloads = $basicCounts['totalDownloads'];
        $totalTransactions = $basicCounts['totalTransactions'];

        // $entertainments = $entertainmentData['entertainments'];

        $totalmovies = $entertainmentData['totalmovies'];
        $totaltvshow = $entertainmentData['totaltvshow'];
        $totalvideo = $entertainmentData['totalvideo'];
        // Use most viewed entertainments for the view (set below)

        $subscriptionData = $subscriptionData['subscriptionData'];

        $subscription_revenue = $revenueData['subscription_revenue'];
        $rent_revenue = $revenueData['rent_revenue'];
        $total_revenue = $revenueData['total_revenue'];
        $subscriptionRevenueChangePercent = $revenueData['subscriptionRevenueChangePercent'];
        $subscriptionRevenueChangeUp = $revenueData['subscriptionRevenueChangeUp'];
        $subscriptionRevenueTrend = $revenueData['subscriptionRevenueTrend'];
        $subscriptionRevenueTrendDates = $revenueData['subscriptionRevenueTrendDates'];
        $rentRevenueChangePercent = $revenueData['rentRevenueChangePercent'];
        $rentRevenueChangeUp = $revenueData['rentRevenueChangeUp'];
        $rentRevenueTrend = $revenueData['rentRevenueTrend'];
        $rentRevenueTrendDates = $revenueData['rentRevenueTrendDates'];
        $totalRevenueChangePercent = $revenueData['totalRevenueChangePercent'];
        $totalRevenueChangeUp = $revenueData['totalRevenueChangeUp'];
        $totalRevenueTrend = $revenueData['totalRevenueTrend'];
        $totalRevenueTrendDates = $revenueData['totalRevenueTrendDates'];

        $count_of_rent_movie = $rentContentData['count_of_rent_movie'];
        $count_of_rent_episode = $rentContentData['count_of_rent_episode'];
        $count_of_rent_video = $rentContentData['count_of_rent_video'];
        $rentContentChangePercent = $rentContentData['rentContentChangePercent'];
        $rentContentChangeUp = $rentContentData['rentContentChangeUp'];
        $rentContentTrend = $rentContentData['rentContentTrend'];
        $rentContentTrendDates = $rentContentData['rentContentTrendDates'];

        $totalreview = $reviewData['totalreview'];
        $reviewsChangePercent = $reviewData['reviewsChangePercent'];
        $reviewsChangeUp = $reviewData['reviewsChangeUp'];
        $reviewsTrend = $reviewData['reviewsTrend'];
        $reviewsTrendDates = $reviewData['reviewsTrendDates'];
        $reviewData = $reviewData['reviewData'];



        $diskType = env('ACTIVE_STORAGE', 'local');
        if ($diskType == 'local') {
            // Use local storage disk
            $totalUsageInBytes = $this->getTotalStorageUsage($diskType);
        } else {
            // Use DigitalOcean Spaces for production
            $totalUsageInBytes = $this->getTotalStorageUsage($diskType);
        }
        // Format the storage usage into a readable format
        $totalUsageFormatted = $this->formatBytes($totalUsageInBytes);
        return view('backend.dashboard.index', compact(
            'defaultFormat','dateRange','totalUsageFormatted','count_of_rent_movie','count_of_rent_episode','rent_revenue','subscription_revenue','totalreview','totalsoontoexpire','total_revenue','allUsers', 'newUsersCount', 'totalDownloads', 'totalTransactions', 'transactions', 'entertainments','totalusers','activeusers','totalSubscribers','totalmovies','totaltvshow','totalvideo','reviewData','subscriptionData','count_of_rent_video',
            'usersChangePercent','usersChangeUp',
            'subsChangePercent','subsChangeUp',
            'soonExpireChangePercent','soonExpireChangeUp',
            'reviewsChangePercent','reviewsChangeUp',
            'subscriptionRevenueChangePercent','subscriptionRevenueChangeUp',
            'rentRevenueChangePercent','rentRevenueChangeUp',
            'totalRevenueChangePercent','totalRevenueChangeUp',
            'rentContentChangePercent','rentContentChangeUp',
            'usersTrend','subsTrend','soonExpireTrend','reviewsTrend',
            'subscriptionRevenueTrend','rentRevenueTrend','totalRevenueTrend','rentContentTrend',
            'usersTrendDates','subsTrendDates','soonExpireTrendDates','reviewsTrendDates',
            'subscriptionRevenueTrendDates','rentRevenueTrendDates','totalRevenueTrendDates','rentContentTrendDates'
        ));
    }

    /**
     * Get optimized user counts with single query
     */
    private function getOptimizedUserCounts($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd, $startOfMonth)
    {
        // Get all users with date filter
        $allUsersQuery = User::where('user_type', 'user');
        if ($startDate && $endDate) {
            $allUsersQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $allUsers = $allUsersQuery->get();

        // Calculate user metrics
        $newUsersCount = User::where('user_type', 'user')
            ->whereDate('created_at', '>=', $startOfMonth)
            ->count();
        $totalusers = $allUsers->count();
        $activeusers = $allUsers->where('status', 1)->count();
        $totalSubscribers = $allUsers->where('is_subscribe', 1)->count();

        // Current and previous period counts
        $usersCurrent = User::where('user_type', 'user')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();
        $usersPrevious = User::where('user_type', 'user')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        // Subscription counts
        $subsCurrent = User::where('user_type', 'user')->where('is_subscribe', 1)
            ->whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $subsPrevious = User::where('user_type', 'user')->where('is_subscribe', 1)
            ->whereBetween('created_at', [$previousStart, $previousEnd])->count();

        // Soon to expire calculations
        $currentDate = Carbon::now();
        $expiryThreshold = $currentDate->copy()->addDays(7);
        $soonExpireUserIds = Subscription::where('status', 'active')
            ->whereDate('end_date', '<=', $expiryThreshold)
            ->pluck('user_id');
        $totalsoontoexpire = $allUsers->whereIn('id', $soonExpireUserIds)->where('status', 1)->count();

        $soonExpireCurrent = User::where('user_type', 'user')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->whereIn('id', $soonExpireUserIds)->count();
        $soonExpirePrevious = User::where('user_type', 'user')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->whereIn('id', $soonExpireUserIds)->count();

        // Calculate percentage changes and trends
        [$usersChangePercent, $usersChangeUp] = $this->computePercentageChange($usersCurrent, $usersPrevious);
        $usersTrend = [$usersPrevious, $usersCurrent];
        $usersTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        [$subsChangePercent, $subsChangeUp] = $this->computePercentageChange($subsCurrent, $subsPrevious);
        $subsTrend = [$subsPrevious, $subsCurrent];
        $subsTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        [$soonExpireChangePercent, $soonExpireChangeUp] = $this->computePercentageChange($soonExpireCurrent, $soonExpirePrevious);
        $soonExpireTrend = [$soonExpirePrevious, $soonExpireCurrent];
        $soonExpireTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        return compact('allUsers', 'newUsersCount', 'totalusers', 'activeusers', 'totalSubscribers',
                      'usersCurrent', 'usersPrevious', 'usersChangePercent', 'usersChangeUp', 'usersTrend', 'usersTrendDates',
                      'subsCurrent', 'subsPrevious', 'subsChangePercent', 'subsChangeUp', 'subsTrend', 'subsTrendDates',
                      'totalsoontoexpire', 'soonExpireCurrent', 'soonExpirePrevious', 'soonExpireChangePercent',
                      'soonExpireChangeUp', 'soonExpireTrend', 'soonExpireTrendDates');
    }

    /**
     * Get basic counts with optimized queries
     */
    private function getBasicCounts()
    {
        $totalDownloads = EntertainmentDownload::count();
        $totalTransactions = SubscriptionTransactions::count();

        return compact('totalDownloads', 'totalTransactions');
    }

    /**
     * Get entertainment data with optimized queries
     * OPTIMIZATION: Use direct count queries instead of loading all records into memory
     */
    private function getEntertainmentData()
    {
        // $entertainments = Entertainment::where('status', 1)->get();
        // $totalmovies = $entertainments->where('type', 'movie')->count();
        // $totaltvshow = $entertainments->where('type', 'tvshow')->count();
        // Use direct count queries instead of ->get() to avoid memory exhaustion with large datasets
        $totalmovies = Entertainment::where('status', 1)->where('type', 'movie')->count();
        $totaltvshow = Entertainment::where('status', 1)->where('type', 'tvshow')->count();
        $totalvideo = Video::where('status', 1)->count();

        // Return empty collection for $entertainments since it's not used in the view
        // (the actual $entertainments used in view comes from getMostViewedEntertainments())
        $entertainments = collect();

       

        // Return empty array for entertainments as most viewed are loaded separately
        return compact('entertainments', 'totalmovies', 'totaltvshow', 'totalvideo');
    }

    /**
     * Get subscription data with optimized queries
     */
    private function getSubscriptionData($startDate, $endDate)
    {
        $subscriptionDataQuery = Subscription::with('user', 'subscription_transaction', 'plan');
        if ($startDate && $endDate) {
            $subscriptionDataQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $subscriptionData = $subscriptionDataQuery->orderBy('created_at', 'desc')->take(6)->get();

        return compact('subscriptionData');
    }

    /**
     * Get revenue data with optimized queries
     */
    private function getRevenueData($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd)
    {
        // Current period revenue - Use created_at (payment date) for consistent totals with chart
        $subscriptionRevenueQuery = SubscriptionTransactions::query()
            ->where('payment_status', 'paid');
        if ($startDate && $endDate) {
            $subscriptionRevenueQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $subscription_revenue = (float) $subscriptionRevenueQuery->sum('amount');

        $rentRevenueQuery = PayperviewTransaction::query();
        if ($startDate && $endDate) {
            $rentRevenueQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $rent_revenue = (float) $rentRevenueQuery->sum('amount');
        $total_revenue = $subscription_revenue + $rent_revenue;

        // Comparison period revenue
        $subscriptionRevenueCurrent = (float) SubscriptionTransactions::where('payment_status', 'paid')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('amount');
        $subscriptionRevenuePrevious = (float) SubscriptionTransactions::where('payment_status', 'paid')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount');
        [$subscriptionRevenueChangePercent, $subscriptionRevenueChangeUp] = $this->computePercentageChange($subscriptionRevenueCurrent, $subscriptionRevenuePrevious);
        $subscriptionRevenueTrend = [$subscriptionRevenuePrevious, $subscriptionRevenueCurrent];
        $subscriptionRevenueTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        $rentRevenueCurrent = (float) PayperviewTransaction::whereBetween('created_at', [$currentStart, $currentEnd])->sum('amount');
        $rentRevenuePrevious = (float) PayperviewTransaction::whereBetween('created_at', [$previousStart, $previousEnd])->sum('amount');
        [$rentRevenueChangePercent, $rentRevenueChangeUp] = $this->computePercentageChange($rentRevenueCurrent, $rentRevenuePrevious);
        $rentRevenueTrend = [$rentRevenuePrevious, $rentRevenueCurrent];
        $rentRevenueTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        $totalRevenueCurrent = $subscriptionRevenueCurrent + $rentRevenueCurrent;
        $totalRevenuePrevious = $subscriptionRevenuePrevious + $rentRevenuePrevious;
        [$totalRevenueChangePercent, $totalRevenueChangeUp] = $this->computePercentageChange($totalRevenueCurrent, $totalRevenuePrevious);
        $totalRevenueTrend = [$totalRevenuePrevious, $totalRevenueCurrent];
        $totalRevenueTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        return compact('subscription_revenue', 'rent_revenue', 'total_revenue', 'subscriptionRevenueChangePercent',
                      'subscriptionRevenueChangeUp', 'subscriptionRevenueTrend', 'subscriptionRevenueTrendDates',
                      'rentRevenueChangePercent', 'rentRevenueChangeUp', 'rentRevenueTrend', 'rentRevenueTrendDates',
                      'totalRevenueChangePercent', 'totalRevenueChangeUp', 'totalRevenueTrend', 'totalRevenueTrendDates');
    }

    /**
     * Get rent content data with optimized queries
     */
    private function getRentContentData($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd)
    {
        $rentMovieQuery = Entertainment::where('type', 'movie')->where('status', 1)->where('movie_access', 'pay-per-view');
        if ($startDate && $endDate) {
            $rentMovieQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $count_of_rent_movie = $rentMovieQuery->count();

        $rentEpisodeQuery = Episode::where('access', 'pay-per-view')->where('status', 1);
        if ($startDate && $endDate) {
            $rentEpisodeQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $count_of_rent_episode = $rentEpisodeQuery->count();

        $rentVideoQuery = Video::where('access', 'pay-per-view')->where('status', 1);
        if ($startDate && $endDate) {
            $rentVideoQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $count_of_rent_video = $rentVideoQuery->count();

        $rentContentCurrent = $count_of_rent_movie + $count_of_rent_episode + $count_of_rent_video;

        $prevRentMovie = Entertainment::where('type', 'movie')->where('status', 1)->where('movie_access', 'pay-per-view')
            ->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $prevRentEpisode = Episode::where('access', 'pay-per-view')->where('status', 1)
            ->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $prevRentVideo = Video::where('access', 'pay-per-view')->where('status', 1)
            ->whereBetween('created_at', [$previousStart, $previousEnd])->count();
        $rentContentPrevious = $prevRentMovie + $prevRentEpisode + $prevRentVideo;

        [$rentContentChangePercent, $rentContentChangeUp] = $this->computePercentageChange($rentContentCurrent, $rentContentPrevious);
        $rentContentTrend = [$rentContentPrevious, $rentContentCurrent];
        $rentContentTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        return compact('count_of_rent_movie', 'count_of_rent_episode', 'count_of_rent_video',
                      'rentContentChangePercent', 'rentContentChangeUp', 'rentContentTrend', 'rentContentTrendDates');
    }

    /**
     * Get review data with optimized queries
     */
    private function getReviewData($startDate, $endDate, $currentStart, $currentEnd, $previousStart, $previousEnd)
    {
        $totalreviewQuery = Review::query();
        if ($startDate && $endDate) {
            $totalreviewQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalreview = $totalreviewQuery->count();

        $reviewsCurrent = Review::whereBetween('created_at', [$currentStart, $currentEnd])->count();
        $reviewsPrevious = Review::whereBetween('created_at', [$previousStart, $previousEnd])->count();
        [$reviewsChangePercent, $reviewsChangeUp] = $this->computePercentageChange($reviewsCurrent, $reviewsPrevious);
        $reviewsTrend = [$reviewsPrevious, $reviewsCurrent];
        $reviewsTrendDates = $this->generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd);

        $reviewDataQuery = Review::with(['entertainment', 'user'])
            ->whereHas('entertainment', function($query) {
                if (isenablemodule('tvshow') == 1 && isenablemodule('movie') == 1) {
                    $query->where('type', 'movie')->orwhere('type', 'tvshow');
                } else {
                    if (isenablemodule('movie') == 1) {
                        $query->where('type', 'movie');
                    }
                    if (isenablemodule('tvshow') == 1) {
                        $query->Where('type', 'tvshow');
                    }
                }
            });
        if ($startDate && $endDate) {
            $reviewDataQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $reviewData = $reviewDataQuery->orderBy('created_at', 'desc')->take(6)->get();

        return compact('totalreview', 'reviewsChangePercent', 'reviewsChangeUp', 'reviewsTrend',
                      'reviewsTrendDates', 'reviewData');
    }

    /**
     * Get most viewed entertainments with optimized query
     */
    private function getMostViewedEntertainments()
    {
        $mostFrequentIds = EntertainmentView::select('entertainment_id')
            ->groupBy('entertainment_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(4)
            ->pluck('entertainment_id')
            ->toArray();

        return Entertainment::whereIn('id', $mostFrequentIds)->get();
    }

    /**
     * Get total storage usage in bytes for the configured disk.
     * The MB amount shown on the dashboard is the sum of all file sizes
     * on the disk (env ACTIVE_STORAGE, default 'local') — includes uploads, media, and app files.
     */
    private function getTotalStorageUsage($disk)
    {
        $totalSize = 0;
        $files = Storage::disk($disk)->allFiles();

        foreach ($files as $file) {
            $fileSize = Storage::disk($disk)->size($file);

            $totalSize += $fileSize;
        }
        return $totalSize;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    public function getRevenuechartData(Request $request, $type)
    {
        // Optional date range filter from request
        [$startDate, $endDate] = $this->parseDateRange($request->get('date_range'));

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        if ($type == 'Year') {

            $monthlyTotalsQuery = SubscriptionTransactions::selectRaw('YEAR(created_at) as year')
                ->selectRaw('MONTH(created_at) as month')
                ->selectRaw('SUM(amount) as total_amount')
                ->where('payment_status', 'paid')
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)');
            if ($startDate && $endDate) {
                $monthlyTotalsQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $monthlyTotals = $monthlyTotalsQuery->get();

            $monthlyPPVQuery = PayperviewTransaction::selectRaw('YEAR(created_at) as year')
                ->selectRaw('MONTH(created_at) as month')
                ->selectRaw('SUM(amount) as amount')
                ->groupByRaw('YEAR(created_at), MONTH(created_at)')
                ->orderByRaw('YEAR(created_at), MONTH(created_at)');
            if ($startDate && $endDate) {
                $monthlyPPVQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $monthlyPayPerViewTotals = $monthlyPPVQuery->get();

            $chartData = [];

            for ($month = 1; $month <= 12; $month++) {
                $subscriptionAmount = 0;
                $payPerViewAmount = 0;

                foreach ($monthlyTotals as $total) {
                    if ((int)$total->month === $month) {
                        $subscriptionAmount = (float)$total->total_amount;
                        break;
                    }
                }

                foreach ($monthlyPayPerViewTotals as $total) {
                    if ((int)$total->month === $month) {
                        $payPerViewAmount = (float)$total->amount;
                        break;
                    }
                }

                $chartData[] = $subscriptionAmount + $payPerViewAmount;
            };

            // Generate localized month abbreviations
            $category = [];
            for ($month = 1; $month <= 12; $month++) {
                $category[] = Carbon::create()->month($month)->locale(app()->getLocale())->translatedFormat('M');
            }
        } else if ($type == 'Month') {
            // Always show a single calendar month: use the month containing endDate (or now)
            $refDate = ($endDate ?? Carbon::now());
            $startOfMonth = $refDate->copy()->startOfMonth();
            $endOfMonth = $refDate->copy()->endOfMonth();
            $currentYear = (int) $startOfMonth->format('Y');
            $currentMonth = (int) $startOfMonth->format('n');

            // Fetch daily totals for the selected month
            $dailyTotals = SubscriptionTransactions::selectRaw('DAY(created_at) as day, COALESCE(SUM(amount), 0) as total_amount')
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $dailyPayPerViewTotals = PayperviewTransaction::selectRaw('DAY(created_at) as day, COALESCE(SUM(amount), 0) as amount')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            // Build day => amount maps for quick lookup
            $subByDay = [];
            foreach ($dailyTotals as $total) {
                $subByDay[(int) $total->day] = (float) $total->total_amount;
            }
            $ppvByDay = [];
            foreach ($dailyPayPerViewTotals as $total) {
                $ppvByDay[(int) $total->day] = (float) $total->amount;
            }

            // Start from first day of month, move week-by-week (week ends on Sunday).
            $chartData = [];
            $category = [];
            $weekStart = $startOfMonth->copy();
            $weekIndex = 1;

            while ($weekStart <= $endOfMonth) {
                $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

                $inMonthStart = $weekStart->copy()->max($startOfMonth);
                $inMonthEnd = $weekEnd->copy()->min($endOfMonth);

                $weekTotal = 0;
                $dayCursor = $inMonthStart->copy();
                while ($dayCursor <= $inMonthEnd) {
                    $dayNum = (int) $dayCursor->format('j');
                    $weekTotal += ($subByDay[$dayNum] ?? 0) + ($ppvByDay[$dayNum] ?? 0);
                    $dayCursor->addDay();
                }

                $chartData[] = $weekTotal;
                $category[] = __('dashboard.week') . ' ' . $weekIndex;
                $weekStart = $weekEnd->copy()->addDay();
                $weekIndex++;
            }
        } else if ($type == 'Week') {
            // Current calendar week (Mon–Sun) – align with Jan calendar
            $weekStart = Carbon::now()->startOfWeek();
            $weekEnd = Carbon::now()->endOfWeek();

            // Sum by date (so weeks spanning two months are correct)
            $subQuery = SubscriptionTransactions::query()
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$weekStart, $weekEnd]);
            if ($startDate && $endDate) {
                $subQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $subByDate = $subQuery->get()
                ->groupBy(fn ($r) => Carbon::parse($r->created_at)->format('Y-m-d'))
                ->map(fn ($rows) => (float) $rows->sum('amount'));

            $ppvByDate = PayperviewTransaction::query()
                ->whereBetween('created_at', [$weekStart, $weekEnd]);
            if ($startDate && $endDate) {
                $ppvByDate->whereBetween('created_at', [$startDate, $endDate]);
            }
            $ppvByDate = $ppvByDate->get()
                ->groupBy(fn ($r) => Carbon::parse($r->created_at)->format('Y-m-d'))
                ->map(fn ($rows) => (float) $rows->sum('amount'));

            $chartData = [];
            $category = [];
            $dayCursor = $weekStart->copy();

            for ($i = 0; $i < 7; $i++) {
                $dateKey = $dayCursor->format('Y-m-d');
                $total = ($subByDate->get($dateKey) ?? 0) + ($ppvByDate->get($dateKey) ?? 0);
                $chartData[] = $total;
                $category[] = $dayCursor->locale(app()->getLocale())->translatedFormat('l');
                $dayCursor->addDay();
            }

        }

        $data = [
            'chartData' => $chartData,
            'category' => $category
        ];

        return response()->json(['data' => $data, 'status' => true])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function getSubscriberChartData(Request $request, $type)
    {
        [$startDate, $endDate] = $this->parseDateRange($request->get('date_range'));

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $plans = Plan::all();

        $plans = Plan::all()->keyBy('id');

        if ($type == 'Year') {
            $monthlyTotalsQuery = Subscription::selectRaw('YEAR(start_date) as year, MONTH(start_date) as month, plan_id')
            ->selectRaw('COUNT(*) as total_subscribers') // Get a comma-separated list of unique user_ids
            ->groupByRaw('YEAR(start_date), MONTH(start_date), plan_id')
            ->orderByRaw('YEAR(start_date), MONTH(start_date), plan_id');
            if ($startDate && $endDate) {
                $monthlyTotalsQuery->whereBetween('start_date', [$startDate, $endDate]);
            }
            $monthlyTotals = $monthlyTotalsQuery->get()->groupBy('plan_id');

            $chartData = [];

            foreach ($plans as $planId => $plan) {
                $planData = [];
                $planName = $this->getLocalizedPlanName($plan->name, $plan->identifier);

                for ($month = 1; $month <= 12; $month++) {
                    $found = false;
                    if (isset($monthlyTotals[$planId])) {
                        foreach ($monthlyTotals[$planId] as $total) {
                            if ((int)$total->month === $month) {
                                $planData[] = $total->total_subscribers;
                                $found = true;
                                break;
                            }
                        }
                    }
                    if (!$found) {
                        $planData[] = 0;
                    }
                }

                $chartData[] = [
                    'name' => $planName,
                    'data' => $planData
                ];
            }


            // Generate localized month abbreviations
            $category = [];
            for ($month = 1; $month <= 12; $month++) {
                $category[] = Carbon::create()->month($month)->locale(app()->getLocale())->translatedFormat('M');
            }
        } else if ($type == 'Month') {

            // Get the start and end of the current month
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            // Fetch daily subscription totals for the current month
            $monthlyDayTotalsQuery = Subscription::selectRaw('DAY(start_date) as day, plan_id')
                ->selectRaw('COUNT(*) as total_subscribers')
                ->whereYear('start_date', $currentYear)
                ->whereMonth('start_date', $currentMonth)
                ->groupBy('day', 'plan_id')
                ->orderBy('day');
            if ($startDate && $endDate) {
                $monthlyDayTotalsQuery->whereBetween('start_date', [$startDate, $endDate]);
            }
            $monthlyDayTotals = $monthlyDayTotalsQuery->get()->groupBy('plan_id');

            $chartData = [];

            // Calculate the total number of weeks in the month (based on 7-day blocks)
            $weeksInMonth = ceil($endOfMonth->day / 7);

            foreach ($plans as $planId => $plan) {
                $planData = [];
                $planName = $this->getLocalizedPlanName($plan->name, $plan->identifier);

                // Loop through each week of the month
                for ($week = 1; $week <= $weeksInMonth; $week++) {
                    $weekTotal = 0;
                    $found = false;

                    // Loop over each day in the current week (7-day block)
                    for ($day = ($week - 1) * 7 + 1; $day <= min($week * 7, $endOfMonth->day); $day++) {
                        // Check if we have data for the current day and plan
                        if (isset($monthlyDayTotals[$planId])) {
                            foreach ($monthlyDayTotals[$planId] as $total) {
                                if ((int)$total->day === $day) {
                                    $weekTotal += $total->total_subscribers;
                                    $found = true;
                                }
                            }
                        }
                    }

                    // Add the total subscribers for this week (or 0 if no data found)
                    $planData[] = $found ? $weekTotal : 0;
                }

                $chartData[] = [
                    'name' => $planName,
                    'data' => $planData
                ];
            }

            // Create categories for the weeks (localized)
            $category = [];
            for ($i = 1; $i <= $weeksInMonth; $i++) {
                $category[] = __('dashboard.week') . " " . $i;
            }
        } else if ($type == 'Week') {

            $currentWeekStartDate = Carbon::now()->startOfWeek();
            $lastDayOfWeek = Carbon::now()->endOfWeek();

            $weeklyDayTotalsQuery = Subscription::selectRaw('DAY(start_date) as day, plan_id')
                ->selectRaw('COUNT(*) as total_subscribers')
                ->whereYear('start_date', $currentYear)
                ->whereMonth('start_date', $currentMonth)
                ->whereBetween('start_date', [$currentWeekStartDate, $lastDayOfWeek])
                ->groupBy('day', 'plan_id')
                ->orderBy('day');
            if ($startDate && $endDate) {
                $weeklyDayTotalsQuery->whereBetween('start_date', [$startDate, $endDate]);
            }
            $weeklyDayTotals = $weeklyDayTotalsQuery->get();

            $chartData = [];

            foreach ($plans as $planId => $plan) {
                $planData = [];
                $planName = $this->getLocalizedPlanName($plan->name, $plan->identifier);

                for ($day = clone $currentWeekStartDate; $day <= $lastDayOfWeek; $day->addDay()) {
                    $found = false;

                    foreach ($weeklyDayTotals as $total) {
                        if ((int)$total->day === $day->day && $total->plan_id == $planId) {
                            $planData[] = $total->total_subscribers;
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $planData[] = 0;
                    }
                }

                $chartData[] = [
                    'name' => $planName,
                    'data' => $planData
                ];
            }
            // Generate localized week day names
            $category = [];
            for ($day = 0; $day < 7; $day++) {
                $category[] = Carbon::now()->startOfWeek()->addDays($day)->locale(app()->getLocale())->translatedFormat('l');
            }
        }

        $data = [

            'chartData' => $chartData,
            'category' => $category

        ];

        return response()->json(['data' => $data, 'status' => true]);
    }

    public function getGenreChartData(Request $request)
    {
       [$startDate, $endDate] = $this->parseDateRange($request->get('date_range'));

        $genreData = Genres::withCount(['entertainmentGenerMappings' => function ($q) use ($startDate, $endDate) {
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
            }])
            ->orderBy('entertainment_gener_mappings_count', 'desc')
            ->limit(5)
            ->get();

        $genreNames = [];
        $entertainmentCounts = [];

        foreach ($genreData as $genre) {
            $genreNames[] = $genre->name;
            $entertainmentCounts[] = $genre->entertainment_gener_mappings_count;
        }


        $data = [

            'chartData' => $entertainmentCounts,
            'category' => $genreNames

        ];


        return response()->json(['data' => $data, 'status' => true]);
    }

    public function getMostwatchChartData(Request $request, $type)
    {
       [$startDate, $endDate] = $this->parseDateRange($request->get('date_range'));

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $entertainmentTypes = [];

        if (isenablemodule('movie') == 1) {
            $entertainmentTypes['movie'] = __('messages.lbl_movies');
        }

        if (isenablemodule('tvshow') == 1) {
            $entertainmentTypes['tvshow'] = __('messages.lbl_tvshows');
        }



        if ($type == 'Year') {
            $monthlyTotalsQuery = EntertainmentView::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, entertainment_id')
                ->selectRaw('COUNT(*) as total_views')
                ->whereYear('created_at', $currentYear)
                ->groupByRaw('YEAR(created_at), MONTH(created_at), entertainment_id')
                ->orderByRaw('YEAR(created_at), MONTH(created_at), entertainment_id');
            if ($startDate && $endDate) {
                $monthlyTotalsQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $monthlyTotals = $monthlyTotalsQuery->get()->groupBy('entertainment_id');

            $chartData = [];

            foreach ($entertainmentTypes as $type => $typeName) {
                $typeData = [];

                for ($month = 1; $month <= 12; $month++) {
                    $totalViews = 0;
                    foreach ($monthlyTotals as $entertainmentId => $totals) {
                        $entertainment = Entertainment::find($entertainmentId);
                        if ($entertainment && $entertainment->type === $type) {
                            foreach ($totals as $total) {
                                if ((int)$total->month === $month) {
                                    $totalViews += $total->total_views;
                                }
                            }
                        }
                    }
                    $typeData[] = $totalViews;
                }

                $chartData[] = [
                    'name' => $typeName,
                    'data' => $typeData
                ];
            }

            // Generate localized month abbreviations
            $category = [];
            for ($month = 1; $month <= 12; $month++) {
                $category[] = Carbon::create()->month($month)->locale(app()->getLocale())->translatedFormat('M');
            }
        } elseif ($type == 'Month') {

            $firstWeek = Carbon::now()->startOfMonth()->week;
            $lastWeek = Carbon::now()->endOfMonth()->week;

            $monthlyWeekTotalsQuery = EntertainmentView::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, WEEK(created_at, 1) as week, entertainment_id')
                ->selectRaw('COUNT(*) as total_views')
                ->whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $currentMonth)
                ->groupBy('year', 'month', 'week', 'entertainment_id')
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('week');
            if ($startDate && $endDate) {
                $monthlyWeekTotalsQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $monthlyWeekTotals = $monthlyWeekTotalsQuery->get()->groupBy('entertainment_id');

            $chartData = [];

            foreach ($entertainmentTypes as $type => $typeName) {
                $typeData = [];

                for ($week = $firstWeek; $week <= $lastWeek; $week++) {
                    $totalViews = 0;
                    foreach ($monthlyWeekTotals as $entertainmentId => $totals) {
                        $entertainment = Entertainment::find($entertainmentId);
                        if ($entertainment && $entertainment->type === $type) {
                            foreach ($totals as $total) {
                                if ((int)$total->week === $week) {
                                    $totalViews += $total->total_views;
                                }
                            }
                        }
                    }
                    $typeData[] = $totalViews;
                }

                $chartData[] = [
                    'name' => $typeName,
                    'data' => $typeData
                ];
            }

            // Create categories for the weeks (localized)
            $category = [];
            for ($week = $firstWeek; $week <= $lastWeek; $week++) {
                $category[] = __('dashboard.week') . " " . ($week - $firstWeek + 1);
            }
        } elseif ($type == 'Week') {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            $weeklyDayTotalsQuery = EntertainmentView::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, DAY(created_at) as day, entertainment_id')
                ->selectRaw('COUNT(*) as total_views')
                ->groupBy('year', 'month', 'day', 'entertainment_id')
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('day');
            if ($startDate && $endDate) {
                $weeklyDayTotalsQuery->whereBetween('created_at', [$startDate, $endDate]);
            } else {
                $weeklyDayTotalsQuery->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
            }
            $weeklyDayTotals = $weeklyDayTotalsQuery->get()->groupBy('entertainment_id');

            $chartData = [];

            foreach ($entertainmentTypes as $type => $typeName) {
                $typeData = [];

                for ($day = 0; $day < 7; $day++) {
                    $date = Carbon::now()->startOfWeek()->addDays($day);
                    $totalViews = 0;
                    foreach ($weeklyDayTotals as $entertainmentId => $totals) {
                        $entertainment = Entertainment::find($entertainmentId);
                        if ($entertainment && $entertainment->type === $type) {
                            foreach ($totals as $total) {
                                if ($total->day == $date->day) {
                                    $totalViews += $total->total_views;
                                }
                            }
                        }
                    }
                    $typeData[] = $totalViews;
                }

                $chartData[] = [
                    'name' => $typeName,
                    'data' => $typeData
                ];
            }

            // Generate localized week day names
            $category = [];
            for ($day = 0; $day < 7; $day++) {
                $category[] = Carbon::now()->startOfWeek()->addDays($day)->locale(app()->getLocale())->translatedFormat('l');
            }
        }

        $data = [

            'chartData' => $chartData,
            'category' => $category

        ];

        return response()->json(['data' => $data, 'status' => true]);
    }

    public function getTopRatedChartData(Request $request)
    {
        // Optional date range filter from request
       [$startDate, $endDate] = $this->parseDateRange($request->get('date_range'));

        $topRatedQuery = Review::select('entertainment_id', DB::raw('AVG(rating) as avg_rating'))
            ->groupBy('entertainment_id')
            ->orderBy('avg_rating', 'desc');
        if ($startDate && $endDate) {
            $topRatedQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $topRatedData = $topRatedQuery->get();

        $entertainmentData = Entertainment::whereIn('id', $topRatedData->pluck('entertainment_id'))
            ->get()
            ->keyBy('id');

        $movieCount = 0;
        $tvShowCount = 0;

        foreach ($topRatedData as $data) {
            $entertainment = $entertainmentData->get($data->entertainment_id);
            if ($entertainment) {
                if ($entertainment->type == 'movie') {
                    $movieCount++;
                } elseif ($entertainment->type == 'tvshow') {
                    $tvShowCount++;
                }
            }
        }

        $chartData = [];
        $category = [];

        // Check for enabled modules and build the chartData and category arrays accordingly
        if (isenablemodule('movie') == 1) {
            $chartData[] = [
                'name' => __('messages.lbl_movies'),
                'data' => [$movieCount] // Use an array for radialBar chart
            ];
            $category[] = __('messages.lbl_movies');
        }

        if (isenablemodule('tvshow') == 1) {
            $chartData[] = [
                'name' => __('messages.lbl_tvshows'),
                'data' => [$tvShowCount] // Use an array for radialBar chart
            ];
            $category[] = __('messages.lbl_tvshows');
        }

        $data = [
            'chartData' => $chartData,
            'category' => $category
        ];

        return response()->json(['data' => $data, 'status' => true]);
    }

    private function parseDateRange($dateRange)
    {
        $startDate = null;
        $endDate = null;

        if ($dateRange) {
            $dates = explode(' to ', $dateRange);
            if (count($dates) == 1) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[0])->endOfDay();
            } elseif (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
            }
        } else {
            // Set default to current year when no date range is provided
            $endDate = Carbon::now()->endOfDay();
            $startDate = Carbon::now()->startOfYear()->startOfDay();
        }

        return [$startDate, $endDate];
    }

    private function resolveComparisonPeriods($startDate, $endDate)
    {
        if ($startDate && $endDate) {
            $currentStart = (clone $startDate)->startOfDay();
            $currentEnd = (clone $endDate)->endOfDay();
        } else {
            $currentStart = Carbon::now()->startOfYear();
            $currentEnd = Carbon::now()->endOfDay();
        }

        $periodDays = $currentStart->diffInDays($currentEnd) + 1;
        $previousEnd = (clone $currentStart)->subDay()->endOfDay();
        $previousStart = (clone $previousEnd)->subDays($periodDays - 1)->startOfDay();

        return [$currentStart, $currentEnd, $previousStart, $previousEnd];
    }

    private function computePercentageChange($currentValue, $previousValue)
    {
        if ($previousValue == 0) {
            if ($currentValue == 0) {
                return [0, true];
            }
            return [100, true];
        }
        $change = (($currentValue - $previousValue) / $previousValue) * 100;
        $isUp = $change >= 0;
        $percent = min(100, abs(round($change, 2)));
        return [$percent, $isUp];
    }

    private function generateTrendDates($previousStart, $previousEnd, $currentStart, $currentEnd)
    {
        $dates = [];
        $previousMidpoint = $previousStart->copy()->addDays($previousStart->diffInDays($previousEnd) / 2);
        $dates[] = $previousMidpoint->toISOString();
        $currentMidpoint = $currentStart->copy()->addDays($currentStart->diffInDays($currentEnd) / 2);
        $dates[] = $currentMidpoint->toISOString();

        return $dates;
    }

}
