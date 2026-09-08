<?php

namespace Modules\Entertainment\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Entertainment\Models\Entertainment;
use Illuminate\Http\Request;
use Modules\Entertainment\Http\Requests\EntertainmentRequest;
use App\Traits\ModuleTrait;
use App\Models\Clip;
use Modules\Constant\Models\Constant;
use Modules\Subscriptions\Models\Plan;
use Modules\Genres\Models\Genres;
use Modules\CastCrew\Models\CastCrew;
use Modules\Entertainment\Services\EntertainmentService;
use Modules\World\Models\Country;
use Modules\Entertainment\Models\Subtitle;
use Illuminate\Support\Facades\Storage;
use Modules\SEO\Models\Seo;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Transformers\ComingSoonResource;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Modules\Season\Models\Season;
use Modules\Episode\Models\Episode;
class EntertainmentsController extends Controller
{
    protected string $exportClass = '\App\Exports\EntertainmentExport';


    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
        }

            protected $entertainmentService;

        public function __construct(EntertainmentService $entertainmentService)
        {
            $this->entertainmentService = $entertainmentService;

            $this->traitInitializeModuleTrait(
                'castcrew.castcrew_title',
                'castcrew',

                'fa-solid fa-clipboard-list'
            );
        }


    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
        ];

        $module_action = 'List';

          $seo = Seo::first();

        $keywords = $seo->meta_keywords ? explode(',', $seo->meta_keywords) : [];

        $export_import = true;
        $export_columns = [
            [
                'value' => 'name',
                'text' => ' Name',
            ]
        ];
        $export_url = route('backend.entertainments.export');

        return view('entertainment::backend.entertainment.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url','keywords'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Entertainment'; // Adjust as necessary for dynamic use

        clearRelatedCache(['entertainment_list'], 'entertainment');


        return $this->performBulkAction(Entertainment::class, $ids, $actionType, $moduleName);
    }



public function store(EntertainmentRequest $request)
{
    try {
        // Delegate all business logic to service
        $result = $this->entertainmentService->storeEntertainment($request);
        
        $entertainment = $result['entertainment'];
        $message = $result['message'];

        // Handle response based on request type
        // Check for AJAX request - also check expectsJson for API-like requests
        if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $entertainment->type == 'movie' ? route('backend.movies.index') : route('backend.tvshows.index')
            ]);
        }

        return redirect()->route($entertainment->type == 'movie' ? 'backend.movies.index' : 'backend.tvshows.index')
            ->with('success', $message);
            
    } catch (\Exception $e) {
        // Handle validation errors (e.g., duplicate meta title)
        if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['meta_title' => [$e->getMessage()]]
            ], 422);
        }
        
        return redirect()->back()
            ->withErrors(['meta_title' => $e->getMessage()])
            ->withInput();
    }
}



    public function update_status(Request $request, Entertainment $id)
    {
        $id->update(['status' => $request->status]);

        clearRelatedCache(['movie_'.$id->id, 'tvshow_'.$id->id], 'entertainment');
        // Dashboard cache is already cleared via model event, but clearing explicitly for immediate effect
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }

        return response()->json(['status' => true, 'message' => __('messages.status_updated_movie')]);
    }


    public function update_is_restricted(Request $request, Entertainment $id)
    {

        $id->update(['is_restricted' => $request->status]);

        clearRelatedCache(['movie_'.$id->id, 'tvshow_'.$id->id], 'entertainment');

        $message='';

        if ($request->status == 1) {
            $message = __('messages.content_added_to_restricted');
        } else {
            $message = __('messages.content_removed_from_restricted');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(int $id)
{
    // Fetch the entertainment data along with related data (SEO settings, etc.)
    $data = Entertainment::where('id', $id)
        ->with([
            'entertainmentGenerMappings',
            'entertainmentCountryMappings',
            'entertainmentStreamContentMappings',
            'entertainmentTalentMappings',
            'subtitles',
            'entertainmentDownloadMappings'
        ])
        ->first();

    // Initialize SEO data as an object
    $seo = (object) [
        'enable_seo' => $data->enable_seo ?? 0,
        'meta_title' => $data->meta_title,
        'meta_keywords' => $data->meta_keywords,
        'meta_description' => $data->meta_description,
        'seo_image' => $data->seo_image,
        'google_site_verification' => $data->google_site_verification,
        'canonical_url' => $data->canonical_url,
        'short_description' => $data->short_description
    ];

    // Set the base URL for the images
    $tmdb_id = $data->tmdb_id;


    $data->thumbnail_url = setBaseUrlWithFileName($data->thumbnail_url,'image',$data->type);
    $data->poster_url = setBaseUrlWithFileName($data->poster_url,'image',$data->type);
    $data->poster_tv_url = setBaseUrlWithFileName($data->poster_tv_url,'image',$data->type);



    // Set trailer URL and video URL based on their types
    if ($data->trailer_url_type == 'Local') {
        $data->trailer_url = setBaseUrlWithFileName($data->trailer_url,'video',$data->type);
    }

    if ($data->video_upload_type == 'Local') {
        $data->video_url_input = setBaseUrlWithFileName($data->video_url_input,'video',$data->type);
    }

    // Fetch constants for dropdowns
    $constants = Constant::whereIn('type', ['upload_type', 'movie_language', 'video_quality', 'subtitle_language'])->where('status', 1)->orderBy('name', 'asc')->get();
    $upload_url_type = $constants->where('type', 'upload_type');
    $movie_language = $constants->where('type', 'movie_language');
    $video_quality = $constants->where('type', 'video_quality');
    $subtitle_language = $constants->where('type', 'subtitle_language');

    // Fetch download-related constants
    $download_url_type = Constant::where('type', 'upload_type')
        ->where('status', 1)
        ->whereIn('name', ['URL', 'Local'])
        ->get();

    // Fetch related data like plans, genres, actors, and directors
    $plan = Plan::where('status', 1)->get();
    $genres = Genres::where('status', 1)->where('deleted_at', null)->orderBy('name', 'asc')->get();
    $actors = CastCrew::where('type', 'actor')->where('deleted_at', null)->orderBy('name', 'asc')->get();
    $directors = CastCrew::where('type', 'director')->where('deleted_at', null)->orderBy('name', 'asc')->get();
    $countries = Country::where('status', 1)->get();
    $mediaUrls = getMediaUrls();
    $assets = ['textarea'];

    // Set the module title based on the type of entertainment (movie or tvshow)
    $module_title = ($data->type === 'tvshow') ? __('tvshow.edit_title') : __('movie.edit_title');

    // Set options for number selection
    $numberOptions = collect(range(1, 10))->mapWithKeys(function ($number) {
        return [$number => $number];
    });

    // Set the genres, countries, actors, and directors data for the view
    $data['genres_data'] = $data->entertainmentGenerMappings->pluck('genre_id')->toArray();
    $data['countries'] = $data->entertainmentCountryMappings->pluck('country_id')->toArray();
    $data['actors'] = $data->entertainmentTalentMappings->pluck('talent_id')->toArray();
    $data['directors'] = $data->entertainmentTalentMappings->pluck('talent_id')->toArray();

    // Pass all required data to the view
    $clips = Clip::where('content_id', $data->id)
        ->where('content_type', $data->type == 'movie' ? 'movie' : 'tv_show')
        ->get();

        $page_type='movie';

    return view('entertainment::backend.entertainment.edit', compact(
        'data', 'seo', 'tmdb_id', 'upload_url_type', 'plan', 'movie_language',
        'genres', 'numberOptions', 'actors', 'directors', 'countries',
        'video_quality', 'mediaUrls', 'assets', 'module_title', 'subtitle_language', 'clips', 'download_url_type', 'page_type'
    ));
}








public function update(EntertainmentRequest $request, $id)
{
    $request_data = $request->all();
    $entertainment = $this->entertainmentService->getById($id);


        // Handle movie access and related options
        if ($request_data['movie_access'] == "pay-per-view") {
            $request_data['release_date'] = null;
        $request_data['download_status'] = 0;
    }

    // Handle video quality embedded codes
    if (!empty($request_data['video_quality_type'])) {
        foreach ($request_data['video_quality_type'] as $key => $type) {
            if ($type === 'Embedded') {
                if (isset($request_data['quality_video_embed_input'][$key])) {
                    $request_data['quality_video_url_input'][$key] = $request_data['quality_video_embed_input'][$key];
                }
            } else if (in_array($type, ['URL', 'YouTube', 'HLS', 'Vimeo', 'x265'])) {
                if (isset($request_data['quality_video_url_input'][$key])) {
                    if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $request_data['quality_video_url_input'][$key], $matches)) {
                        $request_data['quality_video_url_input'][$key] = $matches[1];
                    }
                }
            }
        }
    }

    // Handle trailer embed code
    if ($request->trailer_url_type === 'Embedded') {
        $request_data['trailer_url'] = $request->input('trailer_embedded');
    }

    // Handle video embed code and iframe extraction
    $videoUrl = $request->input('video_url_input');
    $videoType = $request->input('video_upload_type');
    if ($videoType === 'Embedded') {
        $request_data['video_url_input'] = $request->input('video_embedded');
    } else {
        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
            $request_data['video_url_input'] = $matches[1];
        }
    }

    // Handle SEO fields
    $request_data['meta_title'] = $request->input('meta_title');

    // Convert the array of meta keywords into a comma-separated string
    if (isset($requestData['meta_keywords_input']) && !empty($requestData['meta_keywords_input'])) {
        // If meta_keywords is not already an array, make it an array (e.g., splitting by commas)
        if (!is_array($requestData['meta_keywords_input'])) {
            $requestData['meta_keywords_input'] = explode(',', $requestData['meta_keywords_input']);
        }
        // Implode the array back to a comma-separated string
        $requestData['meta_keywords_input'] = implode(',', $requestData['meta_keywords_input']);
    } else {
        $requestData['meta_keywords_input'] = ''; // Fallback if empty or not set
    }

    // Handle meta description similarly to meta_keywords
    $request_data['meta_description'] = $request->input('meta_description', '');

    // If empty or undefined, set it to null or some default value (optional)
    if (empty($request_data['meta_description'])) {
        $request_data['meta_description'] = null; // Optional: Set default if it's empty
    }

    $request_data['google_site_verification'] = $request->input('google_site_verification');
    $request_data['canonical_url'] = $request->input('canonical_url');
    $request_data['short_description'] = $request->input('short_description');

    // Handle SEO Image if it exists
    if ($request->hasFile('seo_image')) {
        $image = $request->file('seo_image');

        // Generate a safe filename for the image
        $originalName = $image->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);

        // Store the image and save only the filename
        $path = $image->storeAs('public/uploads/seo', $safeName);
        $request_data['seo_image'] = basename($path); // Only store the filename, not the full path
    }

    // Handle URLs and image processing
    $request_data['thumbnail_url'] = !empty($request_data['tmdb_id']) ? $request_data['thumbnail_url'] : extractFileNameFromUrl($request_data['thumbnail_url'], $entertainment['type']);
    $request_data['poster_url'] = !empty($request_data['tmdb_id']) ? $request_data['poster_url'] : extractFileNameFromUrl($request_data['poster_url'], $entertainment['type']);
    $request_data['poster_tv_url'] = !empty($request_data['tmdb_id']) ? $request_data['poster_tv_url'] : extractFileNameFromUrl($request_data['poster_tv_url'], $entertainment['type']);
    $request_data['trailer_video'] = extractFileNameFromUrl($request_data['trailer_video'], $entertainment['type']);
    $request_data['video_file_input'] = isset($request_data['video_file_input']) ? extractFileNameFromUrl($request_data['video_file_input'], $entertainment['type']) : null;

    if (isset($request_data['IMDb_rating'])) {
        $request_data['IMDb_rating'] = round($request_data['IMDb_rating'], 1);
    }

    // Retrieve the existing entertainment entry

    // Handle subtitles
    if ($request->has('deleted_subtitles')) {
        $deletedIds = explode(',', $request->deleted_subtitles);
        Subtitle::whereIn('id', $deletedIds)->delete();
    }

    if ($request->enable_subtitle == 1 && $request->has('subtitles')) {
        foreach ($request->subtitles as $key => $subtitleData) {
            $languageCode = $subtitleData['language'] ?? null;
            $file = $subtitleData['subtitle_file'] ?? null;
            $isDefault = isset($subtitleData['is_default']) && $subtitleData['is_default'] == 1;

            if (!$languageCode) continue; // Skip if no language code

            $lang_arr = Constant::where('type', 'subtitle_language')->where('value', $languageCode)->first();
            $existingSubtitle = $entertainment->subtitles()->where('language_code', $languageCode)->first();

            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, ['srt', 'vtt'])) {
                    return back()
                        ->withErrors(["subtitle_file.$key" => 'Only .srt and .vtt files are allowed'])
                        ->withInput();
                }

                $filename = time() . '_' . $key . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                if ($extension === 'srt') {
                    $srtContent = file_get_contents($file->getRealPath());
                    $vttContent = convertSrtToVtt($srtContent);
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.vtt';
                    Storage::disk('public')->put('subtitles/' . $filename, $vttContent);
                } else {
                    $file->storeAs('subtitles', $filename, 'public');
                }

                if ($existingSubtitle) {
                    $existingSubtitle->update([
                        'subtitle_file' => $filename,
                        'is_default' => $isDefault ? 1 : 0,
                        'language' => $lang_arr->name ?? null,
                    ]);
                } else {
                    $entertainment->subtitles()->create([
                        'entertainment_id' => $entertainment->id,
                        'language_code' => $languageCode,
                        'language' => $lang_arr->name ?? null,
                        'subtitle_file' => $filename,
                        'is_default' => $isDefault ? 1 : 0,
                        'type' => 'movie',
                    ]);
                }
            }

            if ($file == null && $existingSubtitle) {
                $existingSubtitle->update([
                    'is_default' => $isDefault ? 1 : 0,
                    'language' => $lang_arr->name ?? null,
                ]);
            }
        }
    }

    // Update the data in the database
    $data = $this->entertainmentService->update($id, $request_data);

    if((isset($data['enable_download_quality']) && $data['enable_download_quality'] == 1)
    || !empty($data['quality_video_download_type'])) {
        $this->entertainmentService->storeDownloads($request_data, $id);
    }

    // Clear cache and return success message
    $cacheKey = $entertainment->type == 'movie' ? 'movie_'.$id : 'tvshow_'.$id;
    clearRelatedCache($cacheKey, 'entertainment');
    $type = $entertainment->type;
    $message = $entertainment->type == 'movie' ?
    trans('messages.update_form_movie') : trans('messages.update_form_tvshow');

    // Check if request is AJAX
    if ($request->ajax()) {
        $redirectUrl = $type == 'movie' ?
            route('backend.movies.index') :
            route('backend.tvshows.index');

        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => $redirectUrl
        ]);
    }

    if ($type == 'movie') {
        return redirect()->route('backend.movies.index')->with('success', $message);
    } else {
        return redirect()->route('backend.tvshows.index')->with('success', $message);
    }
}





    public function destroy(int $id)
    {
       $entertainment = $this->entertainmentService->getById($id);
       $type=$entertainment->type;

       // Check if TV show has seasons or episodes before deletion
       if ($type == 'tvshow') {
           $seasonsCount = Season::where('entertainment_id', $id)->count();
           if ($seasonsCount > 0) {
               // Check if any of these seasons have episodes
               $seasonIds = Season::where('entertainment_id', $id)->pluck('id');
               $episodesCount = Episode::whereIn('season_id', $seasonIds)->count();

               if ($episodesCount > 0) {
                   $errorTitle = __('messages.cannot_delete_tvshow_title');
                   $errorMessage = __('messages.cannot_delete_tvshow_has_seasons_episodes_message');
                   
                   return response()->json([
                       'message' => $errorMessage,
                       'error_title' => $errorTitle,
                       'status' => false
                   ], 200);
               } else {
                   $errorTitle = __('messages.cannot_delete_tvshow_title');
                   $errorMessage = __('messages.cannot_delete_tvshow_has_seasons_message');
                   return response()->json([
                       'message' => $errorMessage,
                       'error_title' => $errorTitle,
                       'status' => false
                   ], 200);
               }
           }
       }

       $entertainment->delete();
       $message = $entertainment->type == 'movie' ?
       trans('messages.delete_form_movie') : trans('messages.delete_form_tvshow');
       $cacheKey = $entertainment->type == 'movie' ? 'movie_'.$id : 'tvshow_'.$id;
       clearRelatedCache($cacheKey, 'entertainment');
       return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)
    {
        $entertainment = $this->entertainmentService->getById($id);
        $type=$entertainment->type;
        $entertainment->restore();
        $message = $entertainment->type == 'movie' ?
        trans('messages.restore_form_movie') : trans('messages.restore_form_tvshow');
        $cacheKey = $entertainment->type == 'movie' ? 'movie_'.$id : 'tvshow_'.$id;
        clearRelatedCache($cacheKey, 'entertainment');
        return response()->json(['message' => $message, 'status' => true], 200);

    }

    public function forceDelete(int $id)
    {
        $entertainment = $this->entertainmentService->getById($id);
        $type=$entertainment->type;
        $entertainment->forceDelete();
        $message = $entertainment->type == 'movie' ?
        trans('messages.permanent_delete_form_movie') : trans('messages.permanent_delete_form_tvshow');
        $cacheKey = $entertainment->type == 'movie' ? 'movie_'.$id : 'tvshow_'.$id;
        clearRelatedCache($cacheKey, 'entertainment');
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function downloadOption(Request $request, int $id){

        $data = Entertainment::where('id',$id)->with('entertainmentDownloadMappings')->first();

        $module_title =__('messages.download_movie');

        $upload_url_type=Constant::where('type','upload_type')
                            ->where('status', 1)
                            ->whereIn('name', ['URL', 'Local'])
                            ->get();
        $video_quality=Constant::where('type','video_quality')->where('status', 1)->get();
        // No cache to clear here - just displaying a view

        return view('entertainment::backend.entertainment.download', compact('data','module_title','upload_url_type','video_quality'));

    }


   public function storeDownloads(Request $request, int $id)
    {
        $data = $request->all();
        $this->entertainmentService->storeDownloads($data, $id);
        $message = trans('messages.set_download_url');
        $entertainment = Entertainment::find($id);
        $cacheKey = $entertainment && $entertainment->type == 'movie' ? 'movie_'.$id : 'tvshow_'.$id;
        clearRelatedCache($cacheKey, 'entertainment');

        return redirect()->route('backend.movies.index')->with('success', $message);
    }


    public function details(int $id)    
    {
        $data = Entertainment::with([
            'entertainmentGenerMappings',
            'entertainmentStreamContentMappings',
            'entertainmentTalentMappings',
            'season',

        ])->findOrFail($id);

        // Get paginated reviews with user relationship
        $perPage = request('per_page', 5); // Default 5 reviews per page
        $reviews = $data->entertainmentReviews()
            ->with('user')
            ->orderBy('rating', 'desc')
            ->paginate($perPage);

        // Get all reviews for statistics (without pagination)
        $allReviews = $data->entertainmentReviews;

       foreach ($data->entertainmentTalentMappings as $talentMapping) {
    $talentProfile = $talentMapping->talentprofile;

    if ($talentProfile) {
        if (in_array($talentProfile->type, ['actor', 'director'])) {
            $talentProfile->file_url =  setBaseUrlWithFileName($talentProfile->file_url,'image','castcrew');
        }
    }
}
        $data->poster_url =setBaseUrlWithFileName($data->poster_url,'image',$data->type);

        $data->formatted_release_date = formatDate($data->release_date);
        if($data->type == "movie"){
            $module_title = __('movie.title');
            $show_name = $data->name;
            $route = 'backend.movies.index';
        }else{
            $module_title = __('tvshow.title');
            $show_name = $data->name;
            $route = 'backend.tvshows.index';
        }

        return view('entertainment::backend.entertainment.details', compact('data', 'reviews', 'allReviews', 'module_title','show_name','route'));
    }

    public function commingSoonDetail(string $id)    
    {
        $todayDate = Carbon::today()->toDateString();

        // Find in Entertainment table
        $entertainment = Entertainment::where(function($query) use ($id) {
                $query->where('slug', $id)->orWhere('id', $id);
            })
            ->where('release_date', '>', $todayDate)
            ->where('status', 1)
            ->with([
                'entertainmentGenerMappings',
                'plan',
                'entertainmentReviews',
                'entertainmentTalentMappings',
                'entertainmentStreamContentMappings',
                'season',
                'clips'
            ])
            ->first();

        // If not found, check Video table
        if (!$entertainment) {
            $entertainment = \Modules\Video\Models\Video::where(function($query) use ($id) {
                    $query->where('slug', $id)->orWhere('id', $id);
                })
                ->where('release_date', '>', $todayDate)
                ->where('status', 1)
                ->with(['plan', 'clips'])
                ->first();
        }

        if (!$entertainment) {
            abort(404, 'Coming soon content not found.');
        }

        $data = new \Modules\Entertainment\Transformers\Backend\ComingSoonResourceV3($entertainment);
        $data = $data->toArray(request());

        $data['trailer_url'] = !empty($entertainment->trailer_url)
            ? (($entertainment->trailer_url_type ?? null) == 'Local'
                ? setBaseUrlWithFileName($entertainment->trailer_url,'video', $data['type'] ?? 'movie')
                : Crypt::encryptString($entertainment->trailer_url))
            : null;
        $data['formatted_release_date'] = formatDate($entertainment->release_date ?? null);
        $data['short_desc'] = $entertainment->short_desc ?? $entertainment->description ?? null;
        $data['entertainmentTalentMappings'] = $entertainment->entertainmentTalentMappings ?? collect([]);

        $data['is_watch_list'] = WatchList::where('entertainment_id', $entertainment->id)
                        ->where('user_id', auth()->id())
                        ->where('profile_id', getCurrentProfile(auth()->id(), request()))
                        ->exists();
        $data['video_url_input'] = $entertainment->video_url_input ?? '';
        $data['video_upload_type'] = $entertainment->video_upload_type ?? 'Local';

        $data['meta_title'] = !empty($entertainment->meta_title) ? $entertainment->meta_title : '';
        $data['short_description'] = !empty($entertainment->short_description) ? $entertainment->short_description : '';
        $data['meta_keywords'] = !empty($entertainment->meta_keywords) ? $entertainment->meta_keywords : '';
        $data['meta_description'] = !empty($entertainment->meta_description) ? $entertainment->meta_description : '';
        $data['seo_image'] = !empty($entertainment->seo_image) ? $entertainment->seo_image : '';
        $data['google_site_verification'] = !empty($entertainment->google_site_verification) ? $entertainment->google_site_verification : '';
        $data['canonical_url'] = !empty($entertainment->canonical_url) ? $entertainment->canonical_url : '';

        return view('frontend::comingsoon-detail', compact('data'));
    }



}
