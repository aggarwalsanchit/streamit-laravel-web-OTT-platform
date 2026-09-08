<?php

namespace Modules\Ad\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Traits\ModuleTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Ad\Http\Requests\CustomAdsSettingRequest;
use Modules\Ad\Models\CustomAdsSetting;
use Modules\Entertainment\Models\Entertainment;
use Modules\Episode\Models\Episode;
use Modules\Video\Models\Video;
use Yajra\DataTables\DataTables;

class CustomAdsSettingController extends Controller
{
    protected string $exportClass = '\App\Exports\CustomAdsExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'Customads', // module title
            'Castads', // module name
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
                'value' => 'name',
                'text' => __('messages.ad_name'),
            ],
            [
                'value' => 'type',
                'text' => __('messages.type'),
            ],
            [
                'value' => 'placement',
                'text' => __('messages.lbl_ad_placement'),
            ],
            [
                'value' => 'redirect_url',
                'text' => __('messages.redirect_url'),
            ],
            [
                'value' => 'target_content_type',
                'text' => __('messages.target_content_type'),
            ],
            [
                'value' => 'target_categories',
                'text' => __('messages.target_categories'),
            ],
            [
                'value' => 'start_date',
                'text' => __('messages.start_date'),
            ],
            [
                'value' => 'end_date',
                'text' => __('messages.end_date'),
            ],
            [
                'value' => 'status',
                'text' => __('messages.lbl_status'),
            ],
        ];
        $export_url = route('backend.customads.export');
        $module_title = __('messages.customads');

        return view('ad::backend.customads.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url', 'module_title'));

    }

    public function index_data(Datatables $datatable, Request $request)
    {
        // Auto-deactivate expired ads before loading data
        CustomAdsSetting::where('status', 1)
            ->where('end_date', '<', now()->toDateString())
            ->update(['status' => 0]);

        $query = CustomAdsSetting::query()->withTrashed()->select([
            'id', 'name', 'type', 'placement', 'redirect_url','max_views', 'target_content_type', 'target_categories', 'start_date', 'end_date', 'status', 'updated_at', 'deleted_at'
        ]);
        
        $filter = $request->filter;

        if (isset($filter['name'])) {
            $query->where('name', 'like', '%'.$filter['name'].'%');
        }

        if (isset($filter['type'])) {
            $query->where('type', $filter['type']);
        }

        if (isset($filter['placement'])) {
            $query->where('placement', $filter['placement']);
        }

        if (isset($filter['target_content_type'])) {
            $query->where('target_content_type', $filter['target_content_type']);
        }

        $isEnglish = strtolower((string) app()->getLocale()) === 'en';

        // Keep this simple and fast: translate only known enum-like values via lang keys.
        // No GoogleTranslate/network calls during datatable rendering.
        $typeLabels = [
            'image' => __('messages.image'),
            'video' => __('messages.video'),
        ];

        $placementLabels = [
            'home_page' => __('messages.home_page'),
            'player' => __('messages.player'),
            'banner' => __('messages.banner'),
        ];

        $targetContentTypeLabels = [
            'movie' => __('messages.movie'),
            'video' => __('messages.video'),
            'tvshow' => __('messages.tvshow'),
            'tv_show' => __('messages.tvshow'),
        ];

        return $datatable->eloquent($query)
            ->editColumn('name', function ($data) {
                return $data->name ?? '--';
            })
           ->editColumn('type', function ($data) use ($isEnglish, $typeLabels) {
               $raw = strtolower((string) ($data->type ?? ''));
               if ($raw === '') return '--';
               return $isEnglish ? ucfirst($raw) : ($typeLabels[$raw] ?? ucfirst($raw));
            })
            ->editColumn('placement', function ($data) use ($isEnglish, $placementLabels) {
                $raw = strtolower((string) ($data->placement ?? ''));
                if ($raw === '') return '--';
                $human = ucwords(str_replace('_', ' ', $raw));
                return $isEnglish ? $human : ($placementLabels[$raw] ?? $human);
            })
            ->editColumn('redirect_url', function ($data) {
                return $data->redirect_url ?? '--';
            })
            ->editColumn('target_content_type', function ($data) use ($isEnglish, $targetContentTypeLabels) {
                $raw = strtolower((string) ($data->target_content_type ?? ''));
                if ($raw === '') return '--';
                $human = $raw === 'tv_show' ? 'TV Show' : ucfirst($raw);
                return $isEnglish ? $human : ($targetContentTypeLabels[$raw] ?? $human);
            })
            ->editColumn('start_date', function ($data) {
                return $data->start_date ? formatDate($data->start_date) : '--';
            })
            ->editColumn('end_date', function ($data) {
                return $data->end_date ? formatDate($data->end_date) : '--';
            })
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="customads" onclick="dataTableRowCheck('.$row->id.',this)">';
            })
            ->addColumn('action', function ($data) {
                return view('ad::backend.customads.action', compact('data'))->render();
            })
            ->editColumn('status', function ($row) {
                $originalStatus = (int) $row->getOriginal('status');
                $endDate = Carbon::parse($row->end_date)->toDateString();
                $today = now()->toDateString();

                if ($originalStatus === 0 && $endDate < $today) {
                    // if ($row->status == 0 && \Carbon\Carbon::parse($row->end_date)->lt(now()))  {
                    $checked = $row->status ? 'checked' : '';
                    $disabled = $row->trashed() ? 'disabled' : '';

                    return '<div class="form-check form-switch">
                        <input type="checkbox"
                            class="form-check-input switch-status-change"
                            data-end-date="'.$row->end_date.'"
                            data-ad-id="'.$row->id.'"
                            '.$checked.'
                            '.$disabled.'
                            title="Status"
                        >
                    </div>';
                } else {
                    $checked = $row->status ? 'checked="checked"' : '';
                    $disabled = $row->trashed() ? 'disabled' : '';

                    return '
                        <div class="form-check form-switch">
                            <input type="checkbox" data-url="'.route('backend.customads.update_status', $row->id).'"
                                data-token="'.csrf_token().'" class="switch-status-change form-check-input"
                                id="datatable-row-'.$row->id.'" name="status" value="'.$row->id.'" '.$checked.' '.$disabled.'>
                        </div>
                    ';
                }
            })
            ->editColumn('updated_at', fn ($data) => formatUpdatedAt($data->updated_at))
            ->rawColumns(['action', 'check', 'status'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $module_title = __('messages.custom_new_ads');
        $page_type='ads';
        return view('ad::backend.customads.create', compact('module_title','page_type'));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(CustomAdsSettingRequest $request)
    {
        try {
            $data = $request->all();
            $type = strtolower($request->input('type'));
            $urlType = strtolower($request->input('url_type'));

            if ($type === 'video') {
                if ($urlType === 'local') {
                    $mediaInput = $request->input('video_file_input');
                    $data['media'] = $mediaInput ? extractFileNameFromUrl($mediaInput,'ads') : null;
                } else {
                    $data['media'] = $request->input('media_url') ?? null;
                }
            } elseif ($type === 'image') {
                if ($urlType === 'local') {
                    if ($request->has('file_url') && ! empty($request->input('file_url'))) {
                        $filename = $request->input('file_url');
                        // Store only the filename, not the full path
                        $data['media'] = extractFileNameFromUrl($filename,'ads');
                    } else {
                        $data['media'] = $data['media'] ?? null;
                    }
                } else {
                    $data['media'] = $request->input('media_url') ?? null;
                }
            }

            $targetCategories = array_map('intval', $request->input('target_categories', []));
            $data['target_categories'] = json_encode($targetCategories);
            $data['status'] = $request->has('status') ? 1 : 0;

            CustomAdsSetting::create($data);

            return redirect()->route('backend.customads.index')->with('success', __('messages.customads_created_successfully'));
        } catch (\Exception $e) {
            // dd($e->getMessage());
            Log::error('CustomAds Store Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return redirect()->back()->withInput()->with('error', __('messages.customads_creation_failed'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $module_title = __('messages.edit_customads');
        $data = CustomAdsSetting::findOrFail($id);
        $type = $data['type'];
        $urlType = $data['url_type'];
        if ($type === 'video') {
            if ($urlType === 'local') {
                $data->video_url_input = setBaseUrlWithFileName($data->media, 'video', 'ads');
            } else {
                $data->media_url = $data->media;
            }
        } elseif ($type === 'image') {
            if ($urlType === 'local') {
                $data->file_url = setBaseUrlWithFileName($data->media, 'image', 'ads');
            } else {
                $data->media_url = $data->media;
            }
        }
        $data->target_categories = json_decode($data->target_categories, true);
        $targetCategoriesOptions = [];
        switch ($data->target_content_type) {
            case 'movie':
                $targetCategoriesOptions = Entertainment::where('type', 'movie')->pluck('name', 'id')->toArray();
                break;

            case 'video':
                $targetCategoriesOptions = Video::pluck('name', 'id')->toArray();
                break;

            case 'tvshow':
                $targetCategoriesOptions = Episode::pluck('name', 'id')->toArray();
                break;

            default:
                $targetCategoriesOptions = [];
        }

        $page_type='ads';
        return view('ad::backend.customads.edit', compact('module_title', 'data', 'targetCategoriesOptions','page_type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CustomAdsSettingRequest $request, $id)
    {
        // dd($request->all());
        $customad = CustomAdsSetting::findOrFail($id);
        $data = $request->all();
        if (empty($data['skip_enabled'])) {
            $data['skip_after'] = null;
        }
        $type = $request->input('type');
        $urlType = $request->input('url_type');

        $media = null;

        if ($type === 'video') {
            if ($urlType === 'local') {
                $mediaInput = $request->input('video_file_input');
                $media = $mediaInput ? extractFileNameFromUrl($mediaInput,'ads') : null;
            } else {
                $media = $request->input('media_url') ?? null;
            }
        } elseif ($type === 'image') {
            if ($urlType === 'local') {
                $fileInput = $request->input('file_url');
                if ($fileInput) {
                    // Store only the filename, not the full path
                    $media = extractFileNameFromUrl($fileInput,'ads');
                } else {
                    $media = null;
                }
            } else {
                $media = $request->input('media_url') ?? null;
            }
        }
        if (! $media) {
            if ($type === $customad->type && $urlType === $customad->url_type) {
                $media = $customad->media;
            } else {
                $media = null;
            }
        }
        $data['media'] = $media;
        $targetCategories = array_map('intval', $request->input('target_categories', []));
        $data['target_categories'] = json_encode($targetCategories);
        $customad->update($data);

        return redirect()->route('backend.customads.index')->with('success', __('messages.customads_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $data = CustomAdsSetting::findOrFail($id);
        $data->delete();
        $message = trans('messages.delete_form');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore($id)
    {
        $data = CustomAdsSetting::withTrashed()->findOrFail($id);
        $data->restore();
        $message = trans('messages.restore_form');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete($id)
    {
        $category = CustomAdsSetting::withTrashed()->findOrFail($id);
        $category->forceDelete();
        $message = trans('messages.permanent_delete_form');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function update_status(Request $request, CustomAdsSetting $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('messages.status_updated')]);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('messages.customads');

        return $this->performBulkAction(CustomAdsSetting::class, $ids, $actionType, $moduleName);
    }

    public function getTargetCategories(Request $request)
    {
        $type = $request->get('type');
        $items = [];
        switch ($type) {
            case 'movie':
                $items = Entertainment::where('type', 'movie')
                    ->select('id', 'name as text')
                    ->where('status', 1)
                    ->get();
                break;

            case 'video':
                $items = Video::select('id', 'name as text')->where('status', 1)->get();
                break;

            case 'tvshow':
                 $items = Episode::join('entertainments', 'episodes.entertainment_id', '=', 'entertainments.id')
                ->select(
                    'episodes.id',
                    DB::raw("CONCAT(episodes.name, ' (', entertainments.name, ')') as text")
                )
                ->where('episodes.status', 1)
                ->get();
                break;

            default:
                return response()->json([], 200);
        }

        return response()->json($items);
    }

    public function reactivate(Request $request, $id)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $ad = CustomAdsSetting::findOrFail($id);
        $ad->start_date = $request->start_date;
        $ad->end_date = $request->end_date;
        $ad->status = 1;
        $ad->save();

        return response()->json(['status' => true, 'message' => __('messages.status_updated')]);
    }
}
