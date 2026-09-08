<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ModuleTrait;
use App\Models\MobileSetting;
use App\Http\Requests\MobileSettingRequest;
use App\Http\Requests\MobileAddSettingRequest;
use Modules\Entertainment\Models\Entertainment;
use Modules\Entertainment\Models\EntertainmentView;
use Illuminate\Support\Facades\DB;
use Modules\Constant\Models\Constant;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\CastCrew\Models\CastCrew;
use Modules\Genres\Models\Genres;
use Illuminate\Support\Str;
use Modules\Video\Models\Video;
use Carbon\Carbon;
use App\Models\UserWatchHistory;

class MobileSettingController extends Controller
{
    protected $module_title, $module_name, $module_icon;

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        // Page Title
        $this->module_title = 'settings.mobile_setting';

        // module name
        $this->module_name = 'mobile-setting';

        $this->module_icon = 'fas fa-cogs';

        $this->traitInitializeModuleTrait(
            'settings.mobile_setting', // module title
            'mobile-setting', // module name
            'fas fa-cogs' // module icon
        );
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $module_action = 'List';

        $data = MobileSetting::orderBy('position', 'asc');

        $typeValue = MobileSetting::where('slug', '!=', 'banner')->where('slug', '!=', 'continue-watching');

        if (isenablemodule('movie') == 0) {
            $movie_slugs = ['latest-movies', 'top-10', 'popular-movies', 'free-movies'];
            $typeValue->whereNotIn('slug', $movie_slugs);
            $data->whereNotIn('slug', $movie_slugs);
        }
        if (isenablemodule('livetv') == 0) {

            $livetv_slug = ['top-channels'];
            $typeValue->whereNotIn('slug', $livetv_slug);
            $data->whereNotIn('slug', $livetv_slug);
        }

        $data = $data->get();
        $typeValue = $typeValue->get();


        $movieList = Entertainment::where('type', 'movie')
            ->released()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $tvshowList = Entertainment::where('type', 'tvshow')
            ->released()
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $videoList = Video::where('status', 1)
            ->whereDate('release_date', '<=', now())
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $channelList = LiveTvChannel::where('status', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('backend.mobile-setting.index', compact('module_action', 'data', 'typeValue', 'movieList', 'tvshowList', 'videoList','channelList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MobileSettingRequest $request)
    {
        $data = $request->all();

        if($request->has('dashboard_select')){

            $data['value']=$data['dashboard_select'];

        }

        if (!empty($data) && isset($data['value']) && !empty($data['value'])) {

            $data['value'] = is_array($data['value']) ? json_encode($data['value']) : $data['value'];
        }else{

            $data['value'] =null;
        }

        $result = MobileSetting::updateOrCreate(['id' => $request->id], $data);

        if ($result->wasRecentlyCreated) {

            $result['slug'] = strtolower(Str::slug($result->name, '-'));

            $result->save();

            if (in_array($result->slug, ['banner', 'continue-watching', 'advertisement', 'rate-our-app'])) {

                $result->value = 1;

                $result->save();
            }

            $message = __('messages.create_form_mobile', ['form' => $data['name'] ?? __($this->module_title)]);
        } else {
            $message = __('messages.update_form_mobile', ['form' => $data['name'] ?? __($this->module_title)]);
        }

        // Mobile settings are cached (see MobileSetting model cache key: 'setting')
        // and also affect multiple homepage/dashboard sections.
        clearRelatedCache(['setting', 'genres', 'genres_v2', 'home_banners'], null);
        clearDashboardCache();
        if ($request->ajax()) {

            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('backend.mobile-setting.index')->with('success', $message);
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = MobileSetting::where('id', $id)->first();
        return response()->json($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = MobileSetting::where('id', $id)->first();

        // Mobile settings are cached (see MobileSetting model cache key: 'setting')
        // and also affect multiple homepage/dashboard sections.
        clearRelatedCache(['setting', 'genres', 'genres_v2', 'home_banners'], null);
        clearDashboardCache();

        $data->delete();
        $message = trans('messages.delete_form_mobile', [
            'form' => $data->name ?? __($this->module_title),
        ]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function getDropdownValue(string $id)
    {
        $data = MobileSetting::where('id', $id)->first();
        $slug = $data->slug;

        $selectedIds = json_decode($data->value, true);

        $selected_values = null;

        $value = null;
        switch ($slug) {
            case 'top-10':
                // For the Top 10 dropdown we want to allow selecting from ALL movies,
                // not just the currently most-viewed ones. The actual "Top 10" logic
                // on the frontend/API still uses the saved IDs and separate ranking
                // logic; this dropdown is only for choosing which movies participate.
                $value = Entertainment::where('type', 'movie')
                    ->released()
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get();

                if (!empty($selectedIds)) {
                    $selected_values = Entertainment::whereIn('id', $selectedIds)
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();
                }
                break;
            case 'latest-movies':
                  // Movies from last 1 year
                  $oneYearAgo = Carbon::now()->subMonths(12);
                  $value = Entertainment::where('type', 'movie')
                             ->where('status', 1)
                             ->whereNull('deleted_at')
                             ->whereDate('release_date', '<=', Carbon::now())
                             ->whereDate('release_date', '>=', $oneYearAgo)
                             ->orderBy('release_date', 'desc')
                             ->get();
                if (!empty($selectedIds)) {
                    $selected_values = Entertainment::whereIn('id', $selectedIds)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereDate('release_date', '<=', Carbon::now())
                        ->get();
                }
                break;
            case 'enjoy-in-your-native-tongue':
                $value = Constant::where('type', 'movie_language')->get();

                if (!empty($selectedIds)) {
                    $selected_values = Constant::whereIn('id', $selectedIds)->get();
                }
                break;
            case 'popular-movies':
                // Most viewed movies from entertainment_view table
                $mostViewedMovieIds = EntertainmentView::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                if ($mostViewedMovieIds->isNotEmpty()) {
                    $value = Entertainment::where('type', 'movie')
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereDate('release_date', '<=', Carbon::now())
                        ->whereIn('id', $mostViewedMovieIds)
                        ->get()
                        ->sortBy(function ($movie) use ($mostViewedMovieIds) {
                            $index = $mostViewedMovieIds->search($movie->id);
                            return $index !== false ? $index : 999999;
                        })
                        ->values();
                } else {
                    $value = collect();
                }

                if (!empty($selectedIds)) {
                    $selected_values = Entertainment::whereIn('id', $selectedIds)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereDate('release_date', '<=', Carbon::now())
                        ->get();
                }
                break;
            case 'popular-tvshows':
                // Most viewed TV shows from entertainment_view table
                $mostViewedTvShowIds = EntertainmentView::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                if ($mostViewedTvShowIds->isNotEmpty()) {
                    $value = Entertainment::where('type', 'tvshow')
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereIn('id', $mostViewedTvShowIds)
                        ->get()
                        ->sortBy(function ($tvshow) use ($mostViewedTvShowIds) {
                            $index = $mostViewedTvShowIds->search($tvshow->id);
                            return $index !== false ? $index : 999999;
                        })
                        ->values();
                } else {
                    $value = collect();
                }

                if (!empty($selectedIds)) {
                    $selected_values = Entertainment::whereIn('id', $selectedIds)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();
                }
                break;
            // case 'popular-tvcategories':
            //     $value = LiveTvCategory::take(10)->get();

            //     if (!empty($selectedIds)) {
            //         $selected_values = LiveTvCategory::whereIn('id', $selectedIds)->get();
            //     }
            //     break;
            case 'popular-videos':
                // Most watched videos from user_watch_histories table where entertainment_type = 'video'
                $mostWatchedVideoIds = UserWatchHistory::select('entertainment_id', DB::raw('COUNT(*) as watch_count'))
                    ->where('entertainment_type', 'video')
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                if ($mostWatchedVideoIds->isNotEmpty()) {
                    $value = Video::where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereIn('id', $mostWatchedVideoIds)
                        ->get()
                        ->sortBy(function ($video) use ($mostWatchedVideoIds) {
                            $index = $mostWatchedVideoIds->search($video->id);
                            return $index !== false ? $index : 999999;
                        })
                        ->values();
                } else {
                    $value = collect();
                }

                if (!empty($selectedIds)) {
                    $selected_values = Video::whereIn('id', $selectedIds)
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->get();
                }
                break;
            case 'top-channels':
                $value = LiveTvChannel::take(10)->get();

                if (!empty($selectedIds)) {
                    $selected_values = LiveTvChannel::whereIn('id', $selectedIds)->where('status',1)->get();
                }
                break;
            case 'your-favorite-personality':
                $value = CastCrew::where('type', 'actor')->get();

                if (!empty($selectedIds)) {
                    $selected_values = CastCrew::whereIn('id', $selectedIds)->get();
                }
                break;
            case '500-free-movies':
                $value = Entertainment::where('type', 'movie')->where('movie_access', 'free')->whereNull('deleted_at')->get();

                if (!empty($selectedIds)) {
                    $selected_values = Entertainment::whereIn('id', $selectedIds)->where('status',1)->whereNull('deleted_at')->get();
                }
                break;
            case 'genre':
                $value = Genres::take(10)->get();

                if (!empty($selectedIds)) {
                    $selected_values = Genres::whereIn('id', $selectedIds)->get();
                }
                break;
            default:
                $type = request('type');
                if (empty($type)) {
                    return response()->json(['message' => 'type is required for this slug'], 422);
                }
                if ($type == 'movie' || $type == 'tvshow') {
                    $value = Entertainment::where('type', $type)
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->orderBy('release_date', 'desc')
                        ->get();

                    if (!empty($selectedIds)) {
                        $selected_values = Entertainment::whereIn('id', $selectedIds)->released()->where('status',1)->whereNull('deleted_at')->get();
                    }
                } elseif ($type == 'video') {
                    $value = Video::whereDate('release_date', '<=', now())
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->orderBy('release_date', 'desc')
                        ->get();

                    if (!empty($selectedIds)) {
                        $selected_values = Video::whereIn('id', $selectedIds)->whereDate('release_date', '<=', now())->where('status',1)->whereNull('deleted_at')->get();
                    }
                } elseif ($type == 'channel') {
                    $value = LiveTvChannel::where('status', 1)
                        ->get();

                    if (!empty($selectedIds)) {
                        $selected_values = LiveTvChannel::whereIn('id', $selectedIds)->where('status',1)->get();
                    }
                } else {
                    return response()->json(['message' => 'Unsupported type'], 422);
                }
                break;
        }

        if ($value && !empty($selectedIds)) {
            $value = $value->reject(function ($item) use ($selectedIds) {
                return in_array($item->id, $selectedIds);
            });
        }

        return response()->json(['selected' => $selected_values, 'available' => $value]);
    }

    public function updatePosition(Request $request)
    {
        $sortedIDs = $request->input('sortedIDs');

        foreach ($sortedIDs as $index => $id) {
            $mobileSetting = MobileSetting::find($id);
            $mobileSetting->position = $index + 1;
            $mobileSetting->save();
        }

        return response()->json(['success' => true]);
    }

    public function getTypeValue($slug)
    {
        $value = collect();
        $type = request('type');

        switch ($slug) {
            case 'top-10':
                $value = Entertainment::where('type', 'movie')
                    ->released()
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->get();
                break;

            case 'latest-movies':
                $oneYearAgo = Carbon::now()->subMonths(12);
                $value = Entertainment::where('type', 'movie')
                             ->where('status', 1)
                             ->whereNull('deleted_at')
                             ->whereDate('release_date', '<=', Carbon::now())
                             ->whereDate('release_date', '>=', $oneYearAgo)
                             ->orderBy('release_date', 'desc')
                             ->get();
                break;

            case 'enjoy-in-your-native-tongue':
                $value = Constant::where('type', 'movie_language')->get();
                break;

            case 'popular-movies':
                $mostViewedMovieIds = EntertainmentView::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                if ($mostViewedMovieIds->isNotEmpty()) {
                    $value = Entertainment::where('type', 'movie')
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereDate('release_date', '<=', Carbon::now())
                        ->whereIn('id', $mostViewedMovieIds)
                        ->get()
                        ->sortBy(function ($movie) use ($mostViewedMovieIds) {
                            $index = $mostViewedMovieIds->search($movie->id);
                            return $index !== false ? $index : 999999;
                        })
                        ->values();
                }
                break;

            case 'popular-tvshows':
                $mostViewedTvShowIds = EntertainmentView::select('entertainment_id', DB::raw('COUNT(*) as view_count'))
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                if ($mostViewedTvShowIds->isNotEmpty()) {
                    $value = Entertainment::where('type', 'tvshow')
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereIn('id', $mostViewedTvShowIds)
                        ->get()
                        ->sortBy(function ($tvshow) use ($mostViewedTvShowIds) {
                            $index = $mostViewedTvShowIds->search($tvshow->id);
                            return $index !== false ? $index : 999999;
                        })
                        ->values();
                }
                break;

            case 'popular-videos':
                $mostWatchedVideoIds = UserWatchHistory::select('entertainment_id', DB::raw('COUNT(*) as watch_count'))
                    ->where('entertainment_type', 'video')
                    ->groupBy('entertainment_id')
                    ->orderByRaw('COUNT(*) DESC')
                    ->pluck('entertainment_id');

                if ($mostWatchedVideoIds->isNotEmpty()) {
                    $value = Video::where('status', 1)
                        ->whereNull('deleted_at')
                        ->whereIn('id', $mostWatchedVideoIds)
                        ->get()
                        ->sortBy(function ($video) use ($mostWatchedVideoIds) {
                            $index = $mostWatchedVideoIds->search($video->id);
                            return $index !== false ? $index : 999999;
                        })
                        ->values();
                }
                break;

            case 'top-channels':
                $value = LiveTvChannel::where('status', 1)->take(10)->get();
                break;

            case 'your-favorite-personality':
                $value = CastCrew::where('type', 'actor')->get();
                break;

            case '500-free-movies':
                $value = Entertainment::where('type', 'movie')
                    ->where('movie_access', 'free')
                    ->whereNull('deleted_at')
                    ->where('status', 1)
                    ->get();
                break;

            case 'genre':
                $value = Genres::where('status', 1)->take(10)->get();
                break;

            default:
                if ($type === 'movie' || $type === 'tvshow') {
                    $value = Entertainment::where('type', $type)
                        ->released()
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->orderBy('release_date', 'desc')
                        ->get();
                } elseif ($type === 'video') {
                    $value = Video::whereDate('release_date', '<=', now())
                        ->where('status', 1)
                        ->whereNull('deleted_at')
                        ->orderBy('release_date', 'desc')
                        ->get();
                } elseif ($type === 'channel') {
                    $value = LiveTvChannel::where('status', 1)
                        ->get();
                }
                break;
        }
        return response()->json($value);
    }

    public function addNewRequest(MobileAddSettingRequest $request)
    {

         // Mobile settings affect dashboard and content caches
        clearRelatedCache(['setting', 'genres', 'home_banners'], null);

        if ($request->has('type') && $request->type != null) {

            if ($request->has('optionvalue') && !empty($request->optionvalue)) {
                $value = json_encode($request->optionvalue);
            } else {
                $value = null;
            }

            $maxPosition = (int) MobileSetting::max('position');
            $mobileSetting = MobileSetting::find($request->id);

            if ($mobileSetting) {
                // Update existing entry without changing the position
                $mobileSetting->update(['name' => $request->name, 'slug' => $request->type, 'value' => $value]);
            } else {
                // Create new entry with a new position
                MobileSetting::create([
                    'id' => $request->id,
                    'name' => $request->name,
                    'slug' => $request->type,
                    'value' => $value,
                    'position' => $maxPosition + 1
                ]);
            }
        }

        $message = __('messages.create_form_mobile', ['form' => $request->name ?? __($this->module_title)]);

        return redirect()->route('backend.mobile-setting.index')->with('success', $message);
    }

    public function addNewRequestSection(Request $request)
    {

        // Mobile settings affect dashboard and content caches
        clearRelatedCache(['genres', 'home_banners'], null);
        if ($request->has('section_type') && $request->section_type != null) {

            if ($request->has('optionvalue') && !empty($request->optionvalue)) {
                $value = json_encode($request->optionvalue);
            } else {
                $value = null;
            }

            $maxPosition = (int) MobileSetting::max('position');

            MobileSetting::create([
                'id' => $request->id,
                'name' => $request->name,
                'slug' => strtolower(Str::slug($request->name, '-')),
                'type' => $request->section_type,
                'value' => $value,
                'position' => $maxPosition + 1
            ]);
        }

        $message = __('messages.create_form_mobile', ['form' => $request->name ?? __($this->module_title)]);

        return redirect()->route('backend.mobile-setting.index')->with('success', $message);
    }
}
