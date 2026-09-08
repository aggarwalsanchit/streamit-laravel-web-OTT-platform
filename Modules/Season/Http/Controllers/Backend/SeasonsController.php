<?php

namespace Modules\Season\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Season\Models\Season;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Season\Http\Requests\SeasonRequest;
use App\Traits\ModuleTrait;
use Modules\Constant\Models\Constant;
use Modules\Entertainment\Models\Entertainment;
use Modules\Season\Services\SeasonService;
use Modules\Subscriptions\Models\Plan;
use App\Services\ChatGTPService;
use Carbon\Carbon;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;


class SeasonsController extends Controller
{
    protected string $exportClass = '\App\Exports\SeasonExport';

    protected $seasonService;
    protected $chatGTPService;

    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }


    public function __construct(SeasonService $seasonService,ChatGTPService $chatGTPService)
    {
        $this->seasonService = $seasonService;
        $this->chatGTPService=$chatGTPService;

        $this->traitInitializeModuleTrait(
            'season.title',
            'seasons',
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
                'value' => 'entertainment_id',
                'text' => __('movie.lbl_tv_show'),
            ],
            [
                'value' => 'status',
                'text' => __('plan.lbl_status'),
            ]
        ];
        $export_url = route('backend.seasons.export');

        $tvshows = Entertainment::where('type','tvshow')->whereNull('deleted_at')->get();

        return view('season::backend.season.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url','tvshows'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Season'; // Adjust as necessary for dynamic use
        clearRelatedCache([], 'season');


        return $this->performBulkAction(Season::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;
        return $this->seasonService->getDataTable($datatable, $filter);
    }


    public function index_list(Request $request)
    {
        $term = trim($request->q);

        $query_data = Season::query();

        if ($request->filled('entertainment_id')) {
            $query_data->where('entertainment_id', $request->entertainment_id);
        }

        $query_data = $query_data->where('status', 1)->get();

        $data = $query_data->map(function ($row) {
            return [
                'id' => $row->id,
                'name' => $row->name,
            ];
        });

        return response()->json($data);
    }



      public function create()
    {

        $upload_url_type=Constant::where('type','upload_type')->where('status', 1)->get();

        $plan=Plan::where('status',1)->get();

        $tvshows=Entertainment::Where('type','tvshow')->where('status', 1)->whereNull('deleted_at')->orderBy('id','desc')->get();

        $imported_tvshow = Entertainment::where('type', 'tvshow')
        ->where('status', 1)
        ->whereNotNull('tmdb_id')
        ->whereNull('deleted_at')
        ->get();

        $assets = ['textarea'];
        $seasons=null;

        $module_title = __('season.new_title');
        $mediaUrls =  getMediaUrls();
        $page_type='season';

        return view('season::backend.season.create', compact('upload_url_type','assets','plan','tvshows','module_title','mediaUrls','imported_tvshow','seasons','page_type'));

    }




    public function store(SeasonRequest $request)
{
    $data = $request->all();

    $data['poster_url'] = !empty($data['tmdb_id']) ? $data['poster_url'] : extractFileNameFromUrl($data['poster_url'],'season');
    $data['poster_tv_url'] = !empty($data['tmdb_id']) ? $data['poster_tv_url'] : extractFileNameFromUrl($data['poster_tv_url'],'season');

    if ($request->trailer_url_type == 'Local') {
        $data['trailer_video'] = extractFileNameFromUrl($data['trailer_video'],'season');
    }

    if ($request->trailer_url_type === 'Embedded') {
        $data['trailer_url'] = $request->input('trailer_embedded');
    }

    if ($request->hasFile('seo_image')) {
        $image = validateUploadedFile($request->file('seo_image'), 'image');
        $safeName = sanitizeUploadedFilename($image, 'image');
        $path = $image->storeAs('public/uploads/seo', $safeName);
        $data['seo_image'] = basename($path);
    } else {
        $data['seo_image'] = $request->input('seo_image');
    }

    $data['meta_title'] = $request->input('meta_title');
     if (isset($data['meta_keywords'])) {
        if (is_array($data['meta_keywords'])) {
            $data['meta_keywords'] = implode(',', $data['meta_keywords']);
        }
    } else {
        $data['meta_keywords'] = '';
    }

    $data['meta_description'] = $request->input('meta_description');
    $data['google_site_verification'] = $request->input('google_site_verification');
    $data['canonical_url'] = $request->input('canonical_url');
    $data['short_description'] = $request->input('short_description');

    $season = $this->seasonService->create($data);

    $season->meta_keywords = $data['meta_keywords'];
    $season->meta_description = $data['meta_description'];
    $season->seo_image = $data['seo_image'];
    $season->save();

    $tvshow_name = Entertainment::where('id', $data['entertainment_id'])->select('name')->first()->name ?? null;

    // Send notification for new season added only when release date is today or earlier
    if (isset($data['status']) && $data['status'] == 1) {
        $releaseDate = $season->release_date ? \Carbon\Carbon::parse($season->release_date)->startOfDay() : null;
        $today = now()->startOfDay();

        if (!$releaseDate || $releaseDate->lessThanOrEqualTo($today)) {
            $notificationData = [
                'notification_type' => 'season_add',
                'id' => $season->id,
                'season_name' => $season->name,
                'tv_show_id'=> (int)$data['entertainment_id'],
                'tvshow_name' => $tvshow_name,
                'posterimage' => setBaseUrlWithFileName($season->poster_url, 'image', 'season'),
                'release_date' => $season->release_date,
            ];
            SendBulkNotification::dispatch($notificationData)->onQueue('notifications');
        }

        // Upcoming notification logic
        $upcomingDays = (int) (setting('upcoming') ?? 0);
        $upcomingThreshold = $today->copy()->addDays($upcomingDays)->endOfDay();

        if ($releaseDate && $releaseDate->greaterThan($today) && $releaseDate->lessThanOrEqualTo($upcomingThreshold)) {
            $daysRemaining = $today->diffInDays($releaseDate, false);
            $upcomingData = [
                'notification_type' => 'upcoming',
                'id' => $season->id,
                'name' => $season->name,
                'content_type' => 'season',
                'release_date' => $season->release_date,
                'description' => $season->description,
                'days' => $daysRemaining,
                'days_remaining' => $daysRemaining,
                'posterimage' => setBaseUrlWithFileName($season->poster_url, 'image', 'season'),
                'tvshow_name' => $tvshow_name,
                'season_name' => $season->name,
            ];
            SendBulkNotification::dispatch($upcomingData)->onQueue('notifications');
        }
    }

    $message = __('messages.create_form_season', ['form' => 'Season']);
    return redirect()->route('backend.seasons.index')->with('success', $message);
}




    public function edit(int $id)
{
    $data = Season::findOrFail($id);
    $tmdb_id = $data->tmdb_id;
    $data->poster_url = setBaseUrlWithFileName($data->poster_url, 'image', 'season');
    $data->poster_tv_url = setBaseUrlWithFileName($data->poster_tv_url, 'image', 'season');

    if ($data->trailer_url_type == 'Local') {
        $data->trailer_url = setBaseUrlWithFileName($data->trailer_url, 'video', 'season');
    }

    $upload_url_type = Constant::where('type', 'upload_type')->where('status', 1)->get();

    $plan = Plan::where('status', 1)->get();

    $assets = ['textarea'];

    $tvshows = Entertainment::where('type', 'tvshow')->where('status', 1)->whereNull('deleted_at')->orderBy('id', 'desc')->get();

    $module_title = __('season.edit_title');

    $mediaUrls = getMediaUrls();

    $page_type='season';

    $seo = (object) [
        'meta_title' => $data->meta_title,
         'meta_keywords' => $data->meta_keywords,
        'meta_description' => $data->meta_description,
        'seo_image' => $data->seo_image,
        'google_site_verification' => $data->google_site_verification,
        'canonical_url' => $data->canonical_url,
        'short_description' => $data->short_description
    ];

    return view('season::backend.season.edit', compact(
        'data',
        'tmdb_id',
        'upload_url_type',
        'plan',
        'tvshows',
        'module_title',
        'mediaUrls',
        'assets',
        'seo',
        'page_type'
    ));
}





public function update(SeasonRequest $request, int $id)
{
    $requestData = $request->all();

    $requestData['poster_url'] = !empty($requestData['tmdb_id']) ? $requestData['poster_url'] : extractFileNameFromUrl($requestData['poster_url'],'season');
    $requestData['poster_tv_url'] = !empty($requestData['tmdb_id']) ? $requestData['poster_tv_url'] : extractFileNameFromUrl($requestData['poster_tv_url'],'season');

    if ($request->trailer_url_type == 'Local') {
        $requestData['trailer_video'] = extractFileNameFromUrl($requestData['trailer_video'],'season');
    }

    if ($request->trailer_url_type === 'Embedded') {
        $requestData['trailer_url'] = $request->input('trailer_embedded');
    }

    if ($request->hasFile('seo_image')) {
        $image = validateUploadedFile($request->file('seo_image'), 'image');
        $safeName = sanitizeUploadedFilename($image, 'image');
        $path = $image->storeAs('public/uploads/seo', $safeName);
        $requestData['seo_image'] = basename($path);
    }

    $requestData['meta_title'] = $request->input('meta_title');

    if (isset($requestData['meta_keywords_input']) && !empty($requestData['meta_keywords_input'])) {
        if (!is_array($requestData['meta_keywords_input'])) {
            $requestData['meta_keywords_input'] = explode(',', $requestData['meta_keywords_input']);
        }
        $requestData['meta_keywords_input'] = implode(',', $requestData['meta_keywords_input']);
    } else {
        $requestData['meta_keywords_input'] = '';
    }

    $requestData['meta_description'] = $request->input('meta_description', '');

    if (empty($requestData['meta_description'])) {
        $requestData['meta_description'] = null;
    }

    $requestData['google_site_verification'] = $request->input('google_site_verification');
    $requestData['canonical_url'] = $request->input('canonical_url');
    $requestData['short_description'] = $request->input('short_description');

    $this->seasonService->update($id, $requestData);

    $message = __('messages.update_form_season', ['form' => 'Season']);
    return redirect()->route('backend.seasons.index')->with('success', $message);
}




    public function destroy(int $id)
    {
        $season = Season::findOrFail($id);
        
        // Check if season has episodes
        if ($season->episodes()->count() > 0) {
            $errorTitle = __('messages.cannot_delete_season_title');
            $errorMessage = __('messages.cannot_delete_season_message');
            return response()->json([
                'message' => $errorMessage,
                'error_title' => $errorTitle,
                'status' => false
            ], 200);
        }

        $this->seasonService->delete($id);

        $message = __('messages.delete_form_season', ['form' => 'Season']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)
    {
        $this->seasonService->restore($id);
        $message = __('messages.restore_form_season', ['form' => 'Season']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete(int $id)
    {
        $this->seasonService->forceDelete($id);

        $message = __('messages.permanent_delete_form_season', ['form' => 'Season']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function update_status(Request $request, Season $id)
    {
        $id->update(['status' => $request->status]);
        clearRelatedCache([], 'season');


        return response()->json(['status' => true, 'message' => __('messages.status_updated_season')]);
    }

    public function ImportSeasonlist(Request $request){

        $tv_show_id=$request->tmdb_id;

        $tvshowjson = $this->seasonService->getSeasonsList($tv_show_id);
        $tvshowDetails = json_decode($tvshowjson, true);

        while($tvshowDetails === null) {

            $tvshowjson = $this->seasonService->getSeasonsList($tv_show_id);
           $tvshowDetails = json_decode($tvshowjson, true);

        }

        if (isset($tvshowDetails['success']) && $tvshowDetails['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $tvshowDetails['status_message']
            ], 400);
        }

        $seasonsData= [];

        if(isset($tvshowDetails['seasons']) && is_array($tvshowDetails['seasons'])) {

            foreach ($tvshowDetails['seasons'] as $season) {
                $seasonlist = [
                    'name' => $season['name'],
                    'season_number'=>$season['season_number'],
                ];

                $seasonsData[] = $seasonlist;
            }
         }
        return response()->json($seasonsData);
     }

     public function ImportSeasonDetails(Request $request){

        $tvshow_id=$request->tvshow_id;
        $season_id=$request->season_id;

        $season=Season::where('tmdb_id', $tvshow_id)->where('season_index',$season_id)->first();

        if(!empty($season)){

            $message = __('season.already_added_season');

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 400);

        }

        $configuration =$this->seasonService->getConfiguration();
        $configurationData = json_decode($configuration, true);

        while($configurationData === null) {

            $configuration =$this->seasonService->getConfiguration();
            $configurationData = json_decode($configuration, true);
        }

        if(isset($configurationData['success']) && $configurationData['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $configurationData['status_message']
            ], 400);
        }

        $seasonData = $this->seasonService->getSeasonsDetails($tvshow_id,$season_id);
        $seasonDetails = json_decode($seasonData, true);

        while($seasonDetails === null) {

            $seasonData = $this->seasonService->getSeasonsDetails($tvshow_id,$season_id );
            $seasonDetails = json_decode($seasonData, true);

        }

        if (isset($seasonDetails['success']) && $seasonDetails['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $seasonDetails['status_message']
            ], 400);
        }

        $seasonvideos = $this->seasonService->getSeasonVideos($tvshow_id,$season_id);
        $seasonvideo = json_decode($seasonvideos, true);

        while ($seasonvideo === null) {

             $seasonvideos = $this->seasonService->getSeasonVideos($tvshow_id,$season_id);
             $seasonvideo = json_decode($seasonvideos, true);
        }

        if (isset($seasonvideo['success']) && $seasonvideo['success'] === false) {
            return response()->json([
                'success' => false,
                'message' => $seasonvideo['status_message']
            ], 400);
        }

        $trailer_url_type=null;
        $trailer_url=null;

        if(isset($seasonvideo['results']) && is_array($seasonvideo['results'])) {

            foreach($seasonvideo['results'] as $video) {

                if($video['type'] == 'Trailer'){

                    $trailer_url_type= $video['site'];
                    $trailer_url='https://www.youtube.com/watch?v='.$video['key'];

                }
            }
        }

        $tvshows = Entertainment::where('tmdb_id',$tvshow_id)->first();

        $data = [

            'poster_url' => $configurationData['images']['secure_base_url'] . 'original' . $seasonDetails['poster_path'],
            'trailer_url_type'=>$trailer_url_type,
            'trailer_url'=>$trailer_url,
            'name' => $seasonDetails['name'],
            'description' => $seasonDetails['overview'],
            'entertainment_id'=>$tvshows->id,
            'access'=>'free',
            'season_index'=>$season_id,
            'tvshow_id'=>$tvshow_id,

        ];

             return response()->json([
                'success' => true,
                'data' => $data,
            ], 200);

     }

     public function generateDescription(Request $request)
     {
         $name = $request->input('name');
         $description = $request->input('description');
         $tvshow=$request->input('tvshow');
         $type=$request->input('type');

         $tvshows=Entertainment::Where('id',$tvshow)->first();

         if( $tvshows){

            $name= $name.'of'.$tvshows->name;
         }

         $result = $this->chatGTPService->GenerateDescription($name, $description, $type);

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

    public function details(int $id)
{
    $data = Season::with([
        'entertainmentdata',
        'episodes',
        'plan',
    ])->findOrFail($id);

    $data->poster_url = setBaseUrlWithFileName($data->poster_url, 'image', 'season');
    $data->poster_tv_url = setBaseUrlWithFileName($data->poster_tv_url, 'image', 'season');
    $data->formatted_release_date = Carbon::parse($data->release_date)->format('d M, Y');

    $meta_title = $data->meta_title ?? 'Default Meta Title';
    $meta_description = $data->meta_description ?? 'Default Meta Description';
    $meta_keywords = $data->meta_keywords ?? 'Default Meta Keywords';
    $favicon_url = 'path/to/favicon.ico';

    $module_title = __('season.title');
    $show_name = $data->name;
    $route = 'backend.seasons.index';

    return view('season::backend.season.details', compact(
        'data',
        'module_title',
        'show_name',
        'route',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'favicon_url'
    ));
}


}