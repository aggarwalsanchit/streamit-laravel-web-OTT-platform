<?php

namespace Modules\Genres\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Genres\Http\Requests\GenresRequest;
use Modules\Genres\Services\GenreService;
use Yajra\DataTables\DataTables;
use Modules\Genres\Models\Genres;
use App\Traits\ModuleTrait;

class GenresController extends Controller
{
    protected string $exportClass = '\App\Exports\GenresExport';
    protected $genreService;

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(GenreService $genreService)
    {
        $this->genreService = $genreService;
        $this->traitInitializeModuleTrait(
            'genres.title',
            'genres',
            'fa-solid fa-clipboard-list'
        );
    }

    public function index(Request $request)
    {
        $module_action = 'List';
        $export_import = true;

        $filter = [
            'status' => $request->status,
        ];

        $export_columns = [
            [
                'value' => 'name',
                'text' => __('messages.name'),
            ],
            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ],
        ];
        $export_url = route('backend.genres.export');

        return view('genres::backend.genres.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url'));
    }


    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = __('genres.title');
        clearRelatedCache('genres', 'genres');
        return $this->performBulkAction(Genres::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;
        return $this->genreService->getDataTable($datatable, $filter);
    }


    public function update_status(Request $request, int $id)
    {
        $this->genreService->updateGenre($id, ['status' => $request->status]);
        clearBannerDataCache();
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        return response()->json(['status' => true, 'message' => __('messages.status_updated_genre')]);
    }

    public function create(Request $request)
    {
        $module_title = __('genres.add_title');
        $page_type='genres';

        return view('genres::backend.genres.create', compact('module_title','page_type'));


    }

    public function store(GenresRequest $request)
    {
        $data = $request->all();
        $data['file_url'] = extractFileNameFromUrl($data['file_url'],'genres');
        $this->genreService->createGenre($data);
        $message = __('messages.create_form_genre', ['form' => 'Genres']);
        return redirect()->route('backend.genres.index')->with('success', $message);
    }

    public function show(int $id)
    {
        return view('genres::show');
    }

    public function edit(int $id)
    {
        $genre = $this->genreService->getGenreById($id);
        $module_title = __('genres.edit_title');
        $page_type='genres';
        return view('genres::backend.genres.edit', compact('genre','module_title','page_type'));
    }

    public function update(GenresRequest $request, int $id)
    {
        $data = $request->all();

        $data['file_url'] = extractFileNameFromUrl($data['file_url'],'genres');

        $this->genreService->getGenreById($id);

        $this->genreService->updateGenre($id, $data);
        $message = __('messages.update_form_genre', ['form' => 'Genres']);
        return redirect()->route('backend.genres.index')->with('success', $message);
    }

    public function destroy(int $id)
    {
        $this->genreService->deleteGenre($id);
        $message = __('messages.delete_form_genre', ['form' => 'Genres']);
        clearBannerDataCache();
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)
    {
        $this->genreService->restoreGenre($id);
        $message = __('messages.restore_form_genre', ['form' => 'Genres']);
        clearBannerDataCache();
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete(int $id)
    {
        $this->genreService->forceDeleteGenre($id);
        $message = __('messages.permanent_delete_form_genre', ['form' => 'Genres']);
        clearBannerDataCache();
        return response()->json(['message' => $message, 'status' => true], 200);
    }

}
