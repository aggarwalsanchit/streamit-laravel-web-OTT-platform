<?php

namespace Modules\Onboarding\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Onboarding\Models\Onboarding;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Onboarding\Http\Requests\OnboardingRequest;
use App\Traits\ModuleTrait;
use Carbon\Carbon;

class OnboardingsController extends Controller
{
    protected string $exportClass = '\App\Exports\OnboardingExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'onboarding.title', // module title
            'onboardings', // module name
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
                'value' => 'title',
                'text' => __('messages.title'),
            ],
            [
                'value' => 'description',
                'text' => __('messages.description'),
            ],
            [
                'value' => 'status',
                'text' => __('messages.lbl_status'),
            ]
        ];
        $export_url = route('backend.onboardings.export');

        return view('onboarding::backend.onboarding.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Onboarding'; // Adjust as necessary for dynamic use
        $messageKey = __('onboarding.title'); // You might want to adjust this based on the action

        return $this->performBulkAction(Onboarding::class, $ids, $actionType, $messageKey, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $query = Onboarding::query()->withTrashed();


        $filter = $request->filter;

        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        // Global search from DataTables (search[value])
        $globalSearch = $request->input('search.value');
        if (!empty($globalSearch)) {
            $query->where(function ($q) use ($globalSearch) {
                $q->where('title', 'LIKE', "%{$globalSearch}%")
                  ->orWhere('description', 'LIKE', "%{$globalSearch}%");
            });
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="onboarding" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('title', function ($data) {

                $imageUrl = setBaseUrlWithFileName($data->file_url, 'image', 'onboarding');
                return view('components.image-name', ['image' => $imageUrl, 'name' => $data->title])->render();
            })
            // Enable column-specific filtering for the rendered Title column
            ->filterColumn('title', function ($query, $keyword) {
                $query->where('title', 'LIKE', "%{$keyword}%");
            })
            ->addColumn('action', function ($data) {
                return view('onboarding::backend.onboarding.action', compact('data'));
            })
            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked="checked"' : '';
                $disabled = $row->trashed() ? 'disabled' : '';
                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" data-url="' . route('backend.onboardings.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                            id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>
                ';
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);
                return $diff < 25 ? $data->updated_at->diffForHumans() : $data->updated_at->isoFormat('llll');
            })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'status', 'check', 'title'])
            ->toJson();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */

      public function create()
    {

        $module_title = __('onboarding.add_title');
        $page_type='onboarding';
      return view('onboarding::backend.onboarding.create', compact('module_title','page_type'));
    }




    public function store(OnboardingRequest $request)
    {
        $data = $request->all();
        if (!empty($data['file_url'])) {
            $data['file_url'] = extractFileNameFromUrl($data['file_url'],'onboarding');
        }
        $onboarding = Onboarding::create($data);
        $message = trans('messages.create_form_mobile', ['form' => __('onboarding.title')]);
        return redirect()->route('backend.onboardings.index')->with('success', $message);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(int $id)
    {
        $onboarding = Onboarding::where('id', $id)->withTrashed()->first();

        $onboarding->file_url = setBaseUrlWithFileName($onboarding->file_url, 'image', 'onboarding');
        $module_title = __('onboarding.edit_title');
        $page_type='onboarding';
    return view('onboarding::backend.onboarding.edit', compact('onboarding', 'module_title', 'page_type'));

    }


    public function update_status(Request $request, int $id)
    {
        $onboarding = Onboarding::where('id', $id)->first();
        $onboarding->update(['status' => $request->status]);
        return response()->json(['status' => true, 'message' => __('messages.status_updated_onboarding')]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(OnboardingRequest $request, Onboarding $onboarding)
    {
        $requestData = $request->all();

        if($request->hasFile('file_url')){
            $requestData['file_url'] = extractFileNameFromUrl($requestData['file_url'], 'onboarding');
        }

        $onboarding->update($requestData);
        $message = trans('messages.update_form_mobile', ['form' => __('onboarding.title')]);
        return redirect()->route('backend.onboardings.index', $onboarding->id)->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy(int $id)
    {
        $data = Onboarding::where('id', $id)->first();
            $data->delete();
            $message = __('messages.delete_form_mobile', ['form' => __('onboarding.title')]);

            return response()->json(['message' => $message, 'status' => true], 200);

    }

    public function restore(int $id)
    {
        $data = Onboarding::withTrashed()->where('id', $id)->first();
        $data->restore();
        $message = trans('messages.restore_form_mobile', ['form' => __('onboarding.title')]);
        return response()->json(['message' => $message, 'status' => true], 200);

    }

    public function forceDelete(int $id)
    {
        $data = Onboarding::withTrashed()->where('id', $id)->first();
        $data->forceDelete();
        $message = __('messages.permanent_delete_form_onboarding', ['form' => __('onboarding.title')]);
        return response()->json(['message' => $message, 'status' => true], 200);
    }
}
