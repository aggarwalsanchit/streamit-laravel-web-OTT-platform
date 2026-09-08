<?php

namespace Modules\Entertainment\Http\Controllers\Backend;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ModuleTrait;
use Yajra\DataTables\DataTables;
use Modules\Entertainment\Models\Review;
use Illuminate\Http\Response;



class ReviewController extends Controller
{

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }

    public function __construct()
    {
        $this->traitInitializeModuleTrait(
            'review.title', // module title
            'review', // module name
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

        return view('entertainment::backend.review.index', compact('module_action', 'filter'));

    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('review.title');
        return $this->performBulkAction(Review::class, $ids, $actionType, $moduleName);
    }

    public function index_data( Datatables $datatable,Request $request)
    {
        $query = Review::query()->with(['user', 'entertainment'])->withTrashed();

        $query->whereHas('entertainment', function($querydata) {
            $movieEnabled = isenablemodule('movie') == 1;
            $tvshowEnabled = isenablemodule('tvshow') == 1;

            if ($movieEnabled && $tvshowEnabled) {

                $querydata->whereIn('type', ['movie', 'tvshow']);
            } elseif ($movieEnabled && !$tvshowEnabled) {

                $querydata->where('type', 'movie');
            } elseif (!$movieEnabled && $tvshowEnabled) {

                $querydata->where('type', 'tvshow');
            } else {

                $querydata->whereNotIn('type', ['movie', 'tvshow']);
            }
        });


        $filter = $request->filter;

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-'.$row->id.'" name="datatable_ids[]" value="'.$row->id.'" data-type="review" onclick="dataTableRowCheck('.$row->id.',this)">';
            })
            ->addColumn('action', function ($data) {
                return view('entertainment::backend.review.action', compact('data'));
            })


            ->editColumn('user_id', function ($review) {
                return view('entertainment::backend.review.user_profile', compact('review'));
            })
            ->filterColumn('user_id', function($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%')->orWhere('last_name', 'like', '%' . $keyword . '%')->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->editColumn('entertainment_id', function($data) {
             return $data->entertainment ? $data->entertainment->name : '';
            })

            ->filterColumn('entertainment_id', function($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('entertainment', function($q) use ($keyword) {
                        $q->where('name', 'like', '%' . $keyword . '%');
                    });
                }
            })

            ->editColumn('type', function($data) {

                    $type = "";

                    switch ($data->entertainment->type) {
                        case 'movie':
                            $type = __('movie.movie');
                            break;
                        case 'tvshow':
                            $type =  __('season.lbl_tv_shows') ;
                            break;
                        default:
                            $type = $data->entertainment->type;
                            break;
                    }

                return  $type;

            })
            ->filterColumn('type', function($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('entertainment', function($q) use ($keyword) {
                        $q->where('type', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->orderColumn('type', function ($query, $order) {
                $query->orderBy('entertainment_id', $order);
            })

            ->editColumn('updated_at', function ($data) {
                $diff =Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('ddd, MMM D, YYYY h:mm A');
                }
            })

            // Email search support is handled above; avoid overriding it
            ->filterColumn('entertainment_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('entertainment', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
                }
            })
            ->rawColumns(['check','user_id','entertainment_id'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);


    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('entertainment::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
     {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        return view('entertainment::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        return view('entertainment::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        //
    }
     /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy(int $id)
    {
        $data = Review::findOrFail($id);

        clearRelatedCache([], 'entertainment');
        $data->delete();
        $formtitle = __('review.title');
        $message = trans('messages.delete_form', ['form' => $formtitle]);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)
    {
        $data = Review::withTrashed()->findOrFail($id);

        $data->restore();
        clearRelatedCache([], 'entertainment');
        $formtitle = __('review.title');
        $message = trans('messages.restore_form', ['form' => $formtitle]);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete(int $id)
    {
        $data = Review::withTrashed()->findOrFail($id);
        $data->forceDelete();
        clearRelatedCache([], 'entertainment');
        $formtitle = __('review.title');
        $message = trans('messages.permanent_delete_form', ['form' => $formtitle]);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

}


