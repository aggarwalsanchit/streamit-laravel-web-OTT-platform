<?php

namespace Modules\Banner\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Banner\Models\Banner;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Banner\Http\Requests\BannerRequest;
use App\Traits\ModuleTrait;
use Modules\Entertainment\Models\Entertainment;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\Banner\Services\BannerService;
use Illuminate\Support\Facades\Cache;
use Modules\Video\Models\Video;

class BannersController extends Controller
{
    protected string $exportClass = '\App\Exports\BannerExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    protected $bannerService;

    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;

        $this->traitInitializeModuleTrait(
            'banner.title', // module title
            'banners', // module name
            'fa-solid fa-clipboard-list' // module icon
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */

    public function index(Request $request)
    {

        $filter = [
            'status' => $request->status,
        ];

        $module_action = 'List';

        $export_import = true;
        $export_columns = [
            [
                'value' => 'type',
                'text' => __('banner.lbl_type'),
            ],
            [
                'value' => 'type_name',
                'text' => __('messages.type_name'),
            ],
            [
                'value' => 'banner_for',
                'text' => __('banner.lbl_banner_for'),
            ],
            [
                'value' => 'status',
                'text' => __('messages.lbl_status'),
            ],

        ];
        $export_url = route('backend.banners.export');
        return view('banner::backend.banner.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('banner.title');

        clearRelatedCache(['banner_list'], 'banner');
        // Also clear Live TV dashboard cache in case LiveTV banners were affected
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }

        return $this->performBulkAction(Banner::class, $ids, $actionType, $moduleName);
    }

    public function update_status(Request $request, Banner $id)
    {
        $id->update(['status' => $request->status]);

        clearRelatedCache('banner_list', 'banner');
        // Invalidate LiveTV dashboard cache so banner changes reflect in API
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }

        return response()->json(['status' => true, 'message' => __('messages.status_updated_banner')]);
    }

    public function index_list($type)
    {
        $names = [];

        if ($type == 'movie' || $type == 'tvshow') {
            $query = Entertainment::where('type', $type)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'thumbnail_url', 'poster_url', 'poster_tv_url', 'tmdb_id');

            if ($type == 'tvshow') {
                // Optimize nested whereHas by using exists subquery
                $query->whereHas('season', function($q) {
                    $q->whereNull('deleted_at')
                        ->where('status', 1)
                        ->whereHas('episodes', function($episodeQuery) {
                            $episodeQuery->whereNull('deleted_at')
                                ->where('status', 1)
                                ->select('id'); // Only select id for exists check
                        })
                        ->select('id'); // Only select id for exists check
                }, '>=', 1); // Ensure at least one season exists
            }

            $names = $query->get()->toArray();
        }
        else if ($type == 'video') {
            $names = Video::where('status', 1)
                ->whereNull('deleted_at')
                ->select('id', 'name', 'thumbnail_url', 'poster_url', 'poster_tv_url')
                ->get()
                ->toArray();
        }
        else if ($type == 'livetv') {
            $names = LiveTvChannel::where('status', 1)
                ->whereNull('deleted_at')
                ->select('id', 'name','thumb_url as thumbnail_url', 'poster_url', 'poster_tv_url')
                ->get()
                ->toArray();
        }

        // -----------------------------
        // Set Image Base URLs Correctly
        // -----------------------------
        foreach ($names as $key => &$value) {

            // THUMBNAIL
            $value['thumbnail_url'] = !empty($value['thumbnail_url'])
                ? setBaseUrlWithFileName($value['thumbnail_url'], 'image', $type)
                : (!empty($value['poster_url']) ? setBaseUrlWithFileName($value['poster_url'], 'image', 'banner') : null);

            // POSTER
            if (!empty($value['poster_url'])) {
                $value['poster_url'] = setBaseUrlWithFileName($value['poster_url'], 'image', $type);
            }

            // POSTER TV
            if (!empty($value['poster_tv_url'])) {
                $value['poster_tv_url'] = setBaseUrlWithFileName($value['poster_tv_url'], 'image', $type);
            }
        }
        unset($value); // prevent reference issues

        return response()->json($names);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        // Select only needed columns for DataTable
        $query = Banner::query()
            ->withTrashed()
            ->select([
                'id', 'title', 'poster_url', 'type', 'type_name', 'banner_for', 
                'status', 'updated_at', 'deleted_at'
            ]);

        // Cache module enablement checks to avoid repeated function calls
        $cacheKey = 'module_enablement_checks';
        $moduleChecks = Cache::remember($cacheKey, 3600, function () {
            return [
                'movie' => isenablemodule('movie') == 1,
                'tvshow' => isenablemodule('tvshow') == 1,
                'video' => isenablemodule('video') == 1,
            ];
        });

        $types = [];

        if ($moduleChecks['movie']) {
            $types[] = 'movie';
        }
        if ($moduleChecks['tvshow']) {
            $types[] = 'tvshow';
        }
        if ($moduleChecks['video']) {
            $types[] = 'video';
        }

        // Always include 'livetv' regardless of other modules
        $types[] = 'livetv';

        $query->where(function ($q) use ($types) {
            $q->whereIn('type', $types)->orWhereNull('type');
        });

        // Read status filter from either filter[column_status] or column_status
        $status = $request->input('filter.column_status', $request->input('column_status', null));
        if ($status !== null && $status !== '') {
            $query->where('status', (int) $status);
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($data) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $data->id . '" name="datatable_ids[]" value="' . $data->id . '" data-type="banner" onclick="dataTableRowCheck(' . $data->id . ',this)">';
            })

            ->addColumn('image', function ($data) {
                $type = 'banner';

                $imageUrl = setBaseUrlWithFileName($data->poster_url,'image',$type);

                return view('components.media-item', ['thumbnail' => $imageUrl, 'name' => '', 'type' => $type])->render();

            })
            ->editColumn('type', function ($data) {
                return $data->type_label_translated ?? '-';
            })
            ->editColumn('type_name', function ($data) {
                return $data->type_name ?? '-';
            })
            ->editColumn('title', function ($data) {
                return $data->title ?? '-';
            })
            ->addColumn('action', function ($data) {
                return view('banner::backend.banner.action', compact('data'));
            })
            ->editColumn('status', function ($data) {
                $checked = '';
                $disabled = '';

                // Check if the status is active
                if ($data->status) {
                    $checked = 'checked="checked"';
                }

                // Check if the record is soft-deleted and disable the checkbox if true
                if ($data->trashed()) {
                    $disabled = 'disabled';
                }
                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" data-url="' . route('backend.banners.update_status', $data->id) . '" data-token="' . csrf_token() . '" class="switch-status-change form-check-input" id="datatable-row-' . $data->id . '" name="status" value="' . $data->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>
                ';
            })

            ->filterColumn('image', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('title', 'like', '%' . $keyword . '%');
                }
            })
            ->orderColumn('image', function ($query, $order) {
                $query->orderBy('title', $order);
            })

            ->editColumn('updated_at', function ($data) {
                $diff = \Carbon\Carbon::now()->diffInHours($data->updated_at);
                return $diff < config('constant.MIN_HOURS_TO_SHOW_DIFF',25) ? $data->updated_at->diffForHumans() : $data->updated_at->isoFormat('llll');
            })
            ->addColumn('banner_for', function ($row) {
                return $row->banner_for_label_translated ?? '-';
            })


            ->filterColumn('banner_for', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->where('banner_for', 'like', '%' . $keyword . '%');
                }
            })

             ->orderColumn('banner_for', function ($query, $order) {
               $query->orderBy('banner_for', $order);
           })
            ->rawColumns(['action', 'status', 'check', 'image'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }





    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */


    public function create()
    {
        $module_title = __('banner.add_title');
        $types = ['movie' => __('messages.movie'), 'tvshow' => __('messages.tvshow') , 'video' => __('messages.video'), 'livetv' => __('messages.livetv')];
        $mediaUrls = getMediaUrls();
        $page_type='banner';
        return view('banner::backend.banner.create', compact('module_title', 'types', 'mediaUrls','page_type'));
    }


    public function store(BannerRequest $request)
    {
        $data = $request->all();

        // Handle promotional type differently
        if ($data['banner_for'] === 'promotional') {
            // For promotional banners: set type and name to null, use title and description
            $data['type'] = null;
            $data['name_id'] = null;
            $data['type_id'] = null;
            $data['type_name'] = null;
            $data['title'] = $data['title'];
            $data['description'] = $data['description'] ?? null;
            // Process file URLs for promotional banners
            $data['file_url'] = extractFileNameFromUrl($data['file_url'], 'banner');
            $data['poster_url'] = extractFileNameFromUrl($data['poster_url'], 'banner');
            $data['poster_tv_url'] = $data['poster_tv_url'] ? extractFileNameFromUrl($data['poster_tv_url'], 'banner') : null;
        } else {
            // For non-promotional banners: set title and description to null, use type and name
            $data['title'] = null;
            $data['description'] = null;
            $data['type_id'] = $data['type_id'];
            $data['type_name'] = $data['type_name'];
            $data['name_id'] = $data['name_id'];

            // For other types, fetch from entertainment table - only select needed columns
            $movie = Entertainment::where('id', $data['type_id'])
                ->where('type', $data['type'])
                ->select('id', 'thumbnail_url', 'poster_url')
                ->first();

            $data['file_url'] = !empty($movie) && $movie->thumbnail_url === $data['file_url'] ? $data['file_url'] : extractFileNameFromUrl($data['file_url'], 'banner');
            $data['poster_url'] = !empty($movie) && $movie->poster_url === $data['poster_url'] ? $data['poster_url'] : extractFileNameFromUrl($data['poster_url'], 'banner');
            $data['poster_tv_url'] = $data['poster_tv_url'] ? extractFileNameFromUrl($data['poster_tv_url'], 'banner') : null;
        }

        $this->bannerService->create($data, $request);

        // Clear LiveTV dashboard cache so new/updated banners reflect in API
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        $title = __('banner.title');
        $message = trans('messages.create_form_banner', ['form' => $title]);
        return redirect()->route('backend.banners.index')->with('success', $message);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Banner $banner)
    {
        $module_title = __('banner.edit_title');
        $types = ['movie' => __('messages.movie'), 'tvshow' => __('messages.tvshow') , 'video' => __('messages.video'), 'livetv' => __('messages.livetv')];
        $names = [];

        $banner['name_id'] = $banner->type_id;
        $banner->file_url  = setBaseUrlWithFileName($banner->file_url,'image','banner');
        $banner->poster_url = setBaseUrlWithFileName($banner->poster_url,'image','banner');
        $banner->poster_tv_url = setBaseUrlWithFileName($banner->poster_tv_url,'image','banner');

        $mediaUrls = getMediaUrls();

        foreach ($types as $type => $label) {
            if ($type == 'movie' || $type == 'tvshow') {
                $query = Entertainment::where('type', $type)
                    ->whereNull('deleted_at')
                    ->select('id', 'name');

                if ($type == 'tvshow') {
                    // Optimize nested whereHas by selecting only id for exists check
                    $query->whereHas('season', function($q) {
                        $q->whereNull('deleted_at')
                            ->where('status', 1)
                            ->whereHas('episodes', function($episodeQuery) {
                                $episodeQuery->whereNull('deleted_at')
                                    ->where('status', 1)
                                    ->select('id'); // Only select id for exists check
                            })
                            ->select('id'); // Only select id for exists check
                    }, '>=', 1); // Ensure at least one season exists
                }

                $names[$type] = $query->pluck('name', 'id');
            } else if ($type == 'livetv') {
                $names[$type] = LiveTvChannel::whereNull('deleted_at')
                    ->select('id', 'name')
                    ->pluck('name', 'id');
            }
            else if ($type == 'video') {
                $names[$type] = Video::whereNull('deleted_at')
                    ->select('id', 'name')
                    ->pluck('name', 'id');
            }
            else if ($type == 'livetv') {
                $names[$type] = LiveTvChannel::whereNull('deleted_at')
                    ->select('id', 'name')
                    ->pluck('name', 'id');
            }
        }
        $page_type='banner';

        return view('banner::backend.banner.edit', compact('module_title', 'types', 'names', 'banner', 'mediaUrls','page_type'));
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */


    public function update(Request $request, Banner $banner)
    {
        $data = $request->all();

        if ($data['banner_for'] === 'promotional') {
            $data['type'] = null;
            $data['name_id'] = null;
            $data['type_id'] = null;
            $data['type_name'] = null;
            $data['title'] = $data['title'];
            $data['description'] = $data['description'] ?? null;
            // Check if URLs are the same as existing banner URLs
            $data['file_url'] = ($banner->file_url === $data['file_url']) ? $data['file_url'] : extractFileNameFromUrl($data['file_url'], 'banner');
            $data['poster_url'] = ($banner->poster_url === $data['poster_url']) ? $data['poster_url'] : extractFileNameFromUrl($data['poster_url'], 'banner');
            $data['poster_tv_url'] = $data['poster_tv_url'] ? (($banner->poster_tv_url === $data['poster_tv_url']) ? $data['poster_tv_url'] : extractFileNameFromUrl($data['poster_tv_url'], 'banner')) : null;

        }else{
            $data['title'] = null;
            $data['description'] = null;
            $data['type_id'] = $request->input('type_id');
            $data['type_name'] = $request->input('type_name');
            $movie = Entertainment::where('id', $data['type_id'])
                ->where('type', $data['type'])
                ->select('id', 'thumbnail_url', 'poster_url')
                ->first();
            $data['file_url'] = !empty($movie) && $movie->thumbnail_url === $data['file_url'] ? $data['file_url'] : extractFileNameFromUrl($data['file_url'], 'banner');
            $data['poster_url'] = !empty($movie) && $movie->poster_url === $data['poster_url'] ? $data['poster_url'] : extractFileNameFromUrl($data['poster_url'], 'banner');
            $data['poster_tv_url'] = $data['poster_tv_url'] ? extractFileNameFromUrl($data['poster_tv_url'], 'banner') : null;
        }



        $banner->update($data);

        // Clear LiveTV dashboard cache so updated banners reflect in API
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }

        $title = __('banner.title');
        $message = trans('messages.update_form_banner', ['form' => $title]);
        return redirect()->route('backend.banners.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy(int $id)    
    {
        $data = Banner::findOrFail($id);
        $data->delete();
        $title = __('banner.title');
        $message = trans('messages.delete_form_banner', ['form' => $title]);

        // Clear LiveTV dashboard cache so removed banners no longer appear
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)
    {
        $data = Banner::withTrashed()->findOrFail($id);
        $data->restore();
        $title = __('banner.title');
        $message = trans('messages.restore_form_banner', ['form' => $title]);

        // Clear LiveTV dashboard cache so restored banners are reflected
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        return response()->json(['message' => $message, 'status' => true], 200);
    }


    public function forceDelete(int $id)
    {
        $category = Banner::withTrashed()->findOrFail($id);
        $category->forceDelete();
        $title = __('banner.title');
        $message = trans('messages.permanent_delete_form_banner', ['form' => $title]);

        // Clear LiveTV dashboard cache so permanently deleted banners are removed
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        return response()->json(['message' => $message,'status' => true], 200);
    }
}



