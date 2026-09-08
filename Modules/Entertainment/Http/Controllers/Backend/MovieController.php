<?php

namespace Modules\Entertainment\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ModuleTrait;
use Yajra\DataTables\DataTables;
use Modules\Constant\Models\Constant;
use Modules\Subscriptions\Models\Plan;
use Modules\Genres\Models\Genres;
use Modules\CastCrew\Models\CastCrew;
use Modules\Entertainment\Trait\ImportMovieTrait;
use Modules\Entertainment\Services\EntertainmentService;
use Modules\Entertainment\Services\MovieService;
use App\Services\ChatGTPService;
use Modules\World\Models\Country;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class MovieController extends Controller
{
     use ImportMovieTrait;
    protected string $exportClass = '\App\Exports\MoiveExport';

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }


        protected $entertainmentService;
        protected $movieService;
        protected $chatGTPService;

        public function __construct(ChatGTPService $chatGTPService, EntertainmentService $entertainmentService, MovieService $movieService)

        {
            $this->entertainmentService = $entertainmentService;
            $this->movieService= $movieService;
            $this->chatGTPService=$chatGTPService;

            $this->traitInitializeModuleTrait(
                'movie.title',
                'movies',
                'fa-solid fa-clipboard-list'
            );
        }


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
                'text' => __('messages.name'),
            ],
            [
                'value' => 'movie_access',
                'text' => __('movie.movie') . ' ' . __('movie.lbl_movie_access'),
            ],
            [
                'value' => 'like_count',
                'text' => __('movie.likes'),
            ],
            [
                'value' => 'watch_count',
                'text' => __('movie.watch'),
            ],
            [
                'value' => 'language',
                'text' => __('movie.lbl_movie_language'),
            ],

            [
                'value' => 'IMDb_rating',
                'text' => __('movie.lbl_imdb_rating'),
            ],

            [
                'value' => 'duration',
                'text' => __('movie.lbl_duration'),
            ],

            [
                'value' => 'release_date',
                'text' => __('movie.lbl_release_date'),
            ],


            [
                'value' => 'is_restricted',
                'text' => __('movie.lbl_age_restricted'),
            ],

            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ]

        ];
        $export_url = route('backend.movies.export');

        $geners=Genres::where('status',1)->where('deleted_at', null)->orderBy('name', 'asc')->get();
        $plan=Plan::where('status',1)->get();

        $movie_language=Constant::where('type','movie_language')->where('status', 1)->get();

        $actors=CastCrew::where('type','actor')->where('deleted_at', null)->orderBy('name', 'asc')->get();
        $directors=CastCrew::where('type','director')->where('deleted_at', null)->orderBy('name', 'asc')->get();

        return view('entertainment::backend.movie.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url','geners','movie_language','plan','actors','directors'));

    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;
        $type='movie';
        return $this->entertainmentService->getDataTable($datatable, $filter, $type);
    }

    /**
     * Show the form for creating a new resource.
     */

        public function create()
        {

            $constants = Constant::whereIn('type', ['upload_type', 'movie_language', 'video_quality','subtitle_language'])->where('status', 1)->get()->groupBy('type');

            $upload_url_type = collect($constants->get('upload_type'))->sortBy('name',0);
            $movie_language = collect($constants->get('movie_language'))->sortBy('name',0);
            $video_quality = collect($constants->get('video_quality'))->sortBy('name',0);
            $subtitle_language = collect($constants->get('subtitle_language'))->sortBy('name',0);

            $download_url_type=Constant::where('type','upload_type')
                            ->where('status', 1)
                            ->whereIn('name', ['URL', 'Local'])
                            ->get();

            $plan = Plan::where('status', 1)->get();
            $genres = Genres::where('status', 1)->orderBy('name', 'asc')->get();

            $numberOptions = collect(range(1, 10))->mapWithKeys(function ($number) {
                return [$number => $number];
            });

            $cast_crew = CastCrew::whereIn('type', ['actor', 'director'])->orderBy('name', 'asc')->get()->groupBy('type');

            $actors = $cast_crew->get('actor', collect());
            $directors = $cast_crew->get('director', collect());
            $countries = Country::where('status', 1)->get();

            $type = 'movie';
            $module_title = __('movie.add_title');
            $mediaUrls = getMediaUrls();
            $assets = ['textarea'];
            $page_type='movie';
            return view('entertainment::backend.entertainment.create', compact('assets',
                'upload_url_type', 'plan', 'movie_language','subtitle_language','genres', 'numberOptions', 'actors', 'directors','countries', 'video_quality', 'type', 'module_title', 'mediaUrls', 'download_url_type', 'page_type'
            ));
         }

         public function generateDescription(Request $request)
         {
             $name = $request->input('name');
             $description = $request->input('description');

             $result = $this->chatGTPService->GenerateDescription($name, $description);

             $result =json_decode( $result, true);

             if (isset($result['error'])) {
                 return response()->json([
                     'success' => false,
                     'message' => $result['error']['message'],
                 ], 400);
             }

             return response()->json([

                 'success' => true,
                 'data' => isset($result['choices'][0]['message']['content']) ? $result['choices'][0]['message']['content'] : null,
             ], 200);
         }




         public function ImportMovie(int $id)
         {
             $result = $this->movieService->importMovie($id);


             if (isset($result['success']) && $result['success'] === false){
                 return response()->json([
                     'success' => false,
                     'message' => $result['status_message']
                 ], 400);
             }

             return response()->json([
                 'success' => true,
                 'data' => $result
             ], 200);
         }

     public function import(Request $request)
    {
        try {
            $request->validate([
                'import_file' => 'required|file|mimes:csv,xlsx,xls,txt|max:10240', // 10MB
            ]);

            $file = validateUploadedFile($request->file('import_file'), 'import');
            $filename = sanitizeUploadedFilename($file, 'import');
            $path = $file->storeAs('imports/movies', $filename);

            \App\Jobs\ImportMoviesJob::dispatch($path, auth()->user()->id);

            return response()->json([
                'success' => true,
                'message' => __('messages.import_background_started'),
                'data' => [
                    'status' => 'success',
                    'message' => 'Your file has been uploaded successfully. Import process has started in the background. You will receive an email notification once the import is completed.'
                ]
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'data' => [
                    'status' => 'error',
                    'message' => 'Failed to upload file. Please try again.'
                ]
            ], 500);
        }
    }


    public function downloadSample()
    {
        return Excel::download(new \App\Exports\ScopedMoviesSampleExport(), 'movies_import_sample.csv', \Maatwebsite\Excel\Excel::CSV);
    }

}


