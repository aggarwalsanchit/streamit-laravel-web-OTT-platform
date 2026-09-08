<?php

namespace Modules\Constant\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Constant\Models\Constant;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Constant\Http\Requests\ConstantRequest;
use App\Traits\ModuleTrait;
use Modules\Constant\Services\ConstantService;

class ConstantsController extends Controller
{
    protected string $exportClass = '\App\Exports\ConstantExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }
    protected $constantService;

    public function __construct(ConstantService $constantService)
    {
        $this->constantService = $constantService;
        $this->traitInitializeModuleTrait(
            'constant.title', // module title
            'constants', // module name
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
         $constants = Constant::all();
         $constants = $this->constantService->getAll(); 
         $module_action = 'List';
     
         $export_import = true;
         $export_columns = [
            [
                'value' => 'type',
                'text' => 'Type',
            ],
         ];
         $export_url = route('backend.constants.export');
     
         return view('constant::index', compact('constants', 'module_action', 'export_import', 'export_columns', 'export_url'));
     }
     




    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Constant';

        return $this->performBulkAction(Constant::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
{
    $query = Constant::query()->withTrashed();

    $filter = $request->filter;

    if (isset($filter['name'])) {
        $query->where('name', $filter['name']);
    }
    if (isset($filter['column_status'])) {
        $query->where('status', $filter['column_status']);
    }

    return $datatable->eloquent($query)
        ->editColumn('name', fn($data) => $data->name)
        ->editColumn('type', function ($data) {
            // Format type name for display
            $type = $data->type;
            $displayType = '';
            
            if ($type == 'video_quality') {
                $displayType = __('constant.video_quality');
            } elseif ($type == 'movie_language') {
                $displayType = __('constant.movie_language');
            } elseif ($type == 'language') {
                $displayType = 'Language';
            } elseif ($type == 'upload_type') {
                $displayType = __('constant.UPLOAD_URL_TYPE');
            } else {
                // Format other types: convert underscores to spaces and capitalize words
                $displayType = ucwords(str_replace('_', ' ', $type));
            }
            
            return $displayType;
        })
        ->addColumn('check', function ($data) {
            return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $data->id . '"
                name="datatable_ids[]" value="' . $data->id . '" data-type="constant" onclick="dataTableRowCheck(' . $data->id . ',this)">';
        })
        ->addColumn('action', function ($data) {
            return view('constant::action', compact('data'));
        })
        ->editColumn('status', function ($row) {
            $checked = $row->status ? 'checked="checked"' : '';
            $disabled = $row->trashed() ? 'disabled' : '';       
            return '
                <div class="form-check form-switch">
                    <input type="checkbox" data-url="' . route('backend.constants.update_status', $row->id) . '"
                        data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                        id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                </div>
            ';
        })        
        ->editColumn('updated_at', function ($data) {
            $diff = Carbon::now()->diffInHours($data->updated_at);
            return $diff < 25 ? $data->updated_at->diffForHumans() : $data->updated_at->isoFormat('llll');
        })
        ->rawColumns(['action', 'status', 'check'])
        ->orderColumns(['id'], '-:column $1')
        ->make(true);
}

    public function update_status(Request $request, constant $id)
    {
        $id->update(['status' => $request->status]);

        return response()->json(['status' => true, 'message' => __('messages.status_updated_constant')]);
    }


    private function formatUpdatedAt($updatedAt)
    {
        $diff = Carbon::now()->diffInHours($updatedAt);
        return $diff < 25 ? $updatedAt->diffForHumans() : $updatedAt->isoFormat('llll');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */

     public function create(Request $request)
{
    $module_title = __('constant.constant_title');
    $page_type = 'constant';
   
    $types = Constant::distinct()->pluck('type')->toArray();
    // Add 'language' type if not already present
    if (!in_array('language', $types)) {
        $types[] = 'language';
    }
    return view('constant::create', compact('module_title', 'types', 'page_type'));
}



    public function store(ConstantRequest $request)
    {
        // Debug: Log all request data
        \Log::info('Constant Store Request:', $request->all());
        
        $data = $request->all();
        
        // Handle language image URL (from media modal) - custom for language name
        if ($request->has('language_image') && !empty($request->language_image)) {
            $imageUrl = $request->language_image;
            $languageName = $request->input('name');
            
            // Validate that the URL points to an image file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['language_image' => 'Language image must be a valid image file (jpg, jpeg, png, gif, webp, svg).']);
            }
            
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $languageName));
            $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }
            $filename = $cleanName . '_language_image.' . $extension;
            
            \Log::info('Store Method - Filename Generation Debug:', [
                'language_name' => $languageName,
                'clean_name' => $cleanName,
                'extension' => $extension,
                'final_filename' => $filename
            ]);
            
            // Use the helper function for image upload
            $data['language_image'] = extractFileNameFromUrl($imageUrl, 'constant');
            
            \Log::info('Language Image Processed:', [
                'language_name' => $languageName,
                'filename' => $data['language_image'],
                'original_url' => $imageUrl
            ]);
        }

        $existingConstant = Constant::where('name', $data['name'])
            ->where('type', $data['type'])
            ->first();

        if ($existingConstant) {
            $existingConstant->update($data);
            $message = 'Constant updated successfully!';
        } else {
            $constant = Constant::create($data);
            $message = trans('messages.create_form_constant');
        }

        return redirect()->route('backend.constants.index', ['type' => $data['type']])->with('success', $message);
    }





    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(Request $request, int $id)
    {
        $module_title = __('constant.constant_edit');
        $page_type = 'constant';
        $data = Constant::find($id);
        $types = Constant::distinct()->pluck('type')->toArray();
        // Add 'language' type if not already present
        if (!in_array('language', $types)) {
            $types[] = 'language';
        }
       
        return view('constant::edit', compact('module_title','data','types','page_type' ));

    
    }

   



    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(ConstantRequest $request, int $id)   
    {
        $data = Constant::find($id);
        if (!$data) {
            return redirect()->route('backend.constants.index')->with('error', 'Constant not found.');
        }

        $validated = $request->validated();

        // Handle language image URL (from media modal) - custom for language name
        if ($request->has('language_image') && !empty($request->language_image)) {
            $imageUrl = $request->language_image;
            $languageName = $request->input('name');
            
            // Validate that the URL points to an image file
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $allowedExtensions)) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['language_image' => 'Language image must be a valid image file (jpg, jpeg, png, gif, webp, svg).']);
            }
            
            // Generate unique filename similar to importMovieJob.php
            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $languageName));
            $cleanName = preg_replace('/\s+/', '_', trim($cleanName));
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'jpg'; // Default extension
            }
            $filename = $cleanName . '_language_image.' . $extension;
            
            
            // Use the helper function for image upload
            $validated['language_image'] = extractFileNameFromUrl($imageUrl, 'language_image');
            
                   }

        $data->update($validated);
        $message = trans('messages.update_form_constant');

        return redirect()->route('backend.constants.index', ['type' => $data->type])->with('success', $message);
    
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy(int $id)
    {
        $data = Constant::Where('id',$id)->first();
        $data->delete();
        $message = trans('messages.delete_form_constant');
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)
    {
        $data = Constant::withTrashed()->Where('id',$id)->first();
        $data->restore();
        $message = trans('messages.restore_form_constant');
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete(int $id)
    {
        $data = Constant::withTrashed()->Where('id',$id)->first();
        $data->forceDelete();
        $message = trans('messages.permanent_delete_form_constant');
        return response()->json(['message' => $message, 'status' => true], 200);
    }
}
