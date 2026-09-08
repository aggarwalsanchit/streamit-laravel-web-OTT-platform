<?php

namespace Modules\Video\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Modules\Video\Models\Video;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Modules\Video\Http\Requests\VideoRequest;
use App\Traits\ModuleTrait;
use Modules\Constant\Models\Constant;
use Modules\Subscriptions\Models\Plan;
use Modules\Video\Models\VideoStreamContentMapping;
use App\Models\Clip;
use Modules\Video\Services\VideoService;
use App\Services\ChatGTPService;
use Modules\Entertainment\Models\Subtitle;
use Illuminate\Support\Facades\Storage;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;

class VideosController extends Controller
{
    protected string $exportClass = '\App\Exports\VideoExport';
    protected $videoService;
    protected $chatGTPService;

    protected $streamContentService;
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct(VideoService $videoService,ChatGTPService $chatGTPService)
    {
        $this->videoService = $videoService;
        $this->chatGTPService=$chatGTPService;
        $this->traitInitializeModuleTrait(
            'video.title', // module title
            'videos', // module name
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
                'text' => __('messages.name'),
            ],
            [
                'value' => 'access',
                'text' => __('video.singular_title') . ' ' . __('movie.lbl_movie_access'),
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
                'value' => 'like',
                'text' => __('messages.like'),
            ],
            [
                'value' => 'watch',
                'text' => __('messages.watch'),
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
        $export_url = route('backend.videos.export');

        $plan=Plan::where('status',1)->get();

        return view('video::backend.video.index', compact('module_action', 'filter', 'export_import', 'export_columns', 'export_url','plan'));
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);
        $actionType = $request->action_type;
        $moduleName = 'Video'; // Adjust as necessary for dynamic use

        return $this->performBulkAction(Video::class, $ids, $actionType, $moduleName);
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $filter = $request->filter;
        return $this->videoService->getDataTable($datatable, $filter);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */

    public function create()
    {
        $constants = Constant::whereIn('type', ['upload_type', 'movie_language', 'video_quality','subtitle_language'])->where('status', 1)->get()->groupBy('type');

        $upload_url_type = $constants->get('upload_type', collect());
        $video_quality = $constants->get('video_quality', collect());
        $movie_language = $constants->get('movie_language', collect());
        $subtitle_language = $constants->get('subtitle_language', collect());
        $download_url_type = Constant::where('type', 'upload_type')
                                        ->where('status', 1)
                                        ->whereIn('name', ['URL', 'Local'])
                                        ->get();
        $plan = Plan::where('status', 1)->get();
        $module_title = __('video.add_title');
        $mediaUrls = getMediaUrls();
        $assets = ['textarea'];
        $page_type='video';
        return view('video::backend.video.create', compact('subtitle_language','upload_url_type','assets', 'plan', 'video_quality', 'module_title', 'mediaUrls', 'movie_language','download_url_type','page_type'));
    }

    public function store(VideoRequest $request)
{
    $data = $request->all();


    // Handle movie access and related options
    if ($data['access'] == "pay-per-view") {
        $data['release_date'] = null;
        $data['download_status'] = 0;
    }

    // Save the full iframe code if type is Embedded
    if ($request->input('video_upload_type') === 'Embedded') {
        $data['video_url_input'] = $request->input('embed_code'); // Save full iframe
    } else {
        // Extract URL if it's an iframe, otherwise use as is
        $videoUrl = $request->input('video_url_input');
        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
            $data['video_url_input'] = $matches[1];
        }

        // For Local, handle as before
        if ($request->input('video_upload_type') === 'Local') {
            $data['video_url_input'] = extractFileNameFromUrl($request->input('video_file_input'),'video');
        }
    }

    // Handle the slug, SEO, and other metadata
    $data['meta_title'] = $request->input('meta_title');
    // $data['meta_keywords'] = $request->input('meta_keywords');
    if (isset($data['meta_keywords']) && is_array($data['meta_keywords'])) {
        $data['meta_keywords'] = implode(',', $data['meta_keywords']);
    } elseif (!isset($data['meta_keywords'])) {
        // Optionally, set a default value if 'meta_keywords' is not set
        $data['meta_keywords'] = '';
    }
    $data['meta_description'] = $request->input('meta_description');
    $data['google_site_verification'] = $request->input('google_site_verification');
    $data['canonical_url'] = $request->input('canonical_url');
    $data['short_description'] = $request->input('short_description');

    // Handle SEO Image (if it exists)
    if ($request->hasFile('seo_image')) {
        $image = $request->file('seo_image');

        // Generate a safe filename for the image
        $originalName = $image->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);

        // Store the image and save the filename
        $path = $image->storeAs('public/uploads/seo', $safeName);
        $data['seo_image'] = basename($path); // Only store the filename, not the full path
    }

    // Handle poster and TV poster URLs
    $data['poster_url'] = extractFileNameFromUrl($data['poster_url'],'video');
    $data['poster_tv_url'] = extractFileNameFromUrl($data['poster_tv_url'],'video');
    $data['thumbnail_url'] = extractFileNameFromUrl($data['thumbnail_url'],'video');
    $data['type'] = 'video';



    // Create the video entry
    $video = Video::create($data);

    if(config('filesystems.active') == 'bunny'){
        $pullBase = rtrim(config('filesystems.bunny_pull_zone'), '/');

        if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
            $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
            $sourceUrl = $pullBase.'/video/video/'.$filename;
            $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
            if ($m3u8) { $video->update(['bunny_video_url' => $m3u8]); }
        }
    }

    if((isset($data['enable_download_quality']) && $data['enable_download_quality'] == 1)
    || !empty($data['quality_video_download_type'])) {
        $this->videoService->storeDownloads($data, $video->id);
    }
    // Handle Clips for Video
    $clipTypes = $request->input('clip_upload_type', []);
    $clipUrls = $request->input('clip_url_input', []);
    $clipFiles = $request->input('clip_file_input', []);
    $clipEmbeds = $request->input('clip_embedded', []);
    $clipPosterUrls = $request->input('clip_poster_url', []);
    $clipTvPosterUrls = $request->input('clip_tv_poster_url', []);
    $clipTitles = $request->input('clip_title', []);

    if (!empty($clipTypes) && is_array($clipTypes)) {
        $max = max(count($clipTypes), count($clipUrls), count($clipFiles), count($clipEmbeds));
        for ($i = 0; $i < $max; $i++) {
            $type = $clipTypes[$i] ?? null;
            if (!$type) { continue; }
            $url = null;
            if ($type === 'Local') {
                $val = $clipFiles[$i] ?? null;
                if ($val) { $url = extractFileNameFromUrl($val,'video'); }
            } elseif ($type === 'Embedded' || $type === 'Embed') {
                $url = $clipEmbeds[$i] ?? null;
            } else {
                $val = $clipUrls[$i] ?? null;
                if ($val) {
                    if (preg_match('/<iframe[^>]+src=[\'\"][^\'\"]+[\'\"]/i', $val, $m) &&
                        preg_match('/src=[\'\"][^\'\"]+[\'\"]/i', $m[0], $m2)) {
                        $url = trim(str_replace(['src=\"','src=\'','\"','\''], '', $m2[0]));
                    }
                    if (!$url) { $url = $val; }
                }
            }
            if ($url) {
                $posterUrl = $clipPosterUrls[$i] ?? null;
                $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                $title = $clipTitles[$i] ?? null;

                $posterUrl = isset($posterUrl) ? extractFileNameFromUrl($posterUrl,'video') : $posterUrl;
                $tvPosterUrl = isset($tvPosterUrl) ? extractFileNameFromUrl($tvPosterUrl,'video') : $tvPosterUrl;

                Clip::create([
                    'content_id' => $video->id,
                    'content_type' => 'video',
                    'type' => $type,
                    'url' => $url,
                    'poster_url' => $posterUrl,
                    'tv_poster_url' => $tvPosterUrl,                    
                    'title' => $title,
                ]);
            }
        }
    }


    // Handle subtitles if enabled
    if ($request->has('enable_subtitle') && $request->enable_subtitle == 1 && $request->has('subtitles')) {
        foreach ($request->subtitles as $index => $subtitle) {
            if (isset($subtitle['subtitle_file']) && $subtitle['subtitle_file']->isValid()) {
                $language = $request->input("subtitles.$index.language");
                $file = $subtitle['subtitle_file'];
                $isDefault = $request->input("subtitles.$index.is_default", false);

                $lang_arr = Constant::where('type', 'subtitle_language')->where('value', $language)->first();

                if ($file) {
                    $extension = strtolower($file->getClientOriginalExtension());
                    if (!in_array($extension, ['srt', 'vtt'])) {
                        throw new \Exception('Only .srt and .vtt files are allowed');
                    }

                    $filename = time() . '_' . $index . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                    // If it's an SRT file, convert it to VTT
                    if ($extension === 'srt') {
                        $srtContent = file_get_contents($file->getRealPath());
                        $vttContent = convertSrtToVtt($srtContent);

                        // Change extension to .vtt
                        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.vtt';

                        // Store the VTT content
                        Storage::disk('public')->put('subtitles/' . $filename, $vttContent);
                    } else {
                        // Store original VTT file
                        $path = $file->storeAs('subtitles', $filename, 'public');
                    }

                    $video->subtitles()->create([
                        'entertainment_id' => $video->id,
                        'language_code' => $language,
                        'language' => $lang_arr->name ?? null,
                        'subtitle_file' => $filename,
                        'is_default' => $isDefault ? 1 : 0,
                        'type' => 'video',
                    ]);
                }
            }
        }
    }

    // Handle video quality if enabled
    if ($request->has('enable_quality') && $request->enable_quality == 1) {
        $qualityVideoUrl = $request->quality_video_url_input ?? [];
        $videoQuality = $request->video_quality ?? [];
        $videoQualityType = $request->video_quality_type ?? [];
        $qualityVideoFile = $request->quality_video ?? [];
        $qualityVideoEmbed = $request->quality_video_embed ?? [];
        $videoQualityUrl = $request->video_quality_url ?? null; // Single hidden field for Local type

        if (!empty($videoQuality) && (!empty($qualityVideoUrl) || !empty($qualityVideoFile) || !empty($qualityVideoEmbed) || !empty($videoQualityUrl)) && !empty($videoQualityType)) {
            foreach ($videoQuality as $index => $quality) {
                if ($quality != '' && $videoQualityType[$index] != '') {
                    $url = '';
                    if ($videoQualityType[$index] === 'Embedded') {
                        $url = $qualityVideoEmbed[$index] ?? '';
                    } else if ($videoQualityType[$index] === 'Local') {
                        // Check for video_quality_url (single hidden field) first, then fallback to array
                        if (!empty($videoQualityUrl)) {
                            $url = extractFileNameFromUrl($videoQualityUrl, 'video');
                        } elseif (isset($qualityVideoFile[$index]) && !empty($qualityVideoFile[$index])) {
                            $url = extractFileNameFromUrl($qualityVideoFile[$index], 'video');
                        }
                    } else {
                        $url = $qualityVideoUrl[$index] ?? '';
                    }

                    if (!empty($url)) {
                        VideoStreamContentMapping::create([
                            'video_id' => $video->id,
                            'url' => $url,
                            'type' => $videoQualityType[$index],
                            'quality' => $quality,
                        ]);
                    }
                }
            }
        }
    }

    // Send notifications after creating the video
    if (isset($data['status']) && $data['status'] == 1) {
        $releaseDate = $video->release_date ? \Carbon\Carbon::parse($video->release_date)->startOfDay() : null;
        $today = now()->startOfDay();

        if (!$releaseDate || $releaseDate->lessThanOrEqualTo($today)) {
            $notificationData = [
                'notification_type' => 'video_add',
                'id' => $video->id,
                'video_name' => $video->name,
                'release_date' => $video->release_date,
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
                'id' => $video->id,
                'name' => $video->name,
                'content_type' => 'video',
                'release_date' => $video->release_date,
                'description' => $video->description,
                'days' => $daysRemaining,
                'days_remaining' => $daysRemaining,
                'posterimage' => $video->poster_url ?? null,
            ];
            SendBulkNotification::dispatch($upcomingData)->onQueue('notifications');
        }
    }


    $message = trans('messages.create_form_video', ['type' => 'Video']);

    // Check if request is AJAX
    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('backend.videos.index')
        ]);
    }

    // Redirect to the video list page with a success message
    return redirect()->route('backend.videos.index')->with('success', $message);
}




    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit(int $id)    
    {
        $data = Video::where('id', $id)->with(['VideoStreamContentMappings', 'subtitles', 'videoDownloadMappings'])->first();

        if (!$data) {
            return redirect()->route('backend.videos.index')
                ->with('error', __('video.error_not_found'));
        }

        $data->poster_url = setBaseUrlWithFileName($data->poster_url, 'image', 'video');
        $data->poster_tv_url = setBaseUrlWithFileName($data->poster_tv_url, 'image', 'video');
        $data->thumbnail_url = setBaseUrlWithFileName($data->thumbnail_url, 'image', 'video');

        if($data->trailer_url_type == 'Local'){
            $data->trailer_url_type = setBaseUrlWithFileName($data->trailer_url, 'video', 'video');
        }

        if($data->video_upload_type == 'Local'){
            $data->video_url_input = setBaseUrlWithFileName($data->video_url_input, 'video', 'video');
        }

        $upload_url_type = Constant::where('type', 'upload_type')->where('status', 1)->get();
        $plan = Plan::where('status', 1)->get();
        $video_quality = Constant::where('type', 'video_quality')->where('status', 1)->get();
        $subtitle_language = Constant::where('type', 'subtitle_language')->where('status', 1)->get();
        $mediaUrls = getMediaUrls();
        $assets = ['textarea'];
        $module_title = __('video.edit_title');
        $movie_language = Constant::where('type', 'movie_language')->where('status', 1)->get();
        $download_url_type = Constant::where('type', 'upload_type')
                                        ->where('status', 1)
                                        ->whereIn('name', ['URL', 'Local'])
                                        ->get();
        $seo = (object)[
        'meta_title' => $data->meta_title ?? '',
        'meta_keywords' => $data->meta_keywords ?? '',
        'meta_description' => $data->meta_description ?? '',
        'seo_image' => $data->seo_image ?? '',
        'google_site_verification' => $data->google_site_verification ?? '',
        'canonical_url' => $data->canonical_url ?? '',
        'short_description' => $data->short_description ?? ''
    ];

        $clips = Clip::where('content_id', $data->id)
                    ->where('content_type', 'video')
                    ->get();

        $page_type='video';

        return view('video::backend.video.edit', compact(
            'data',
            'upload_url_type',
            'plan',
            'video_quality',
            'module_title',
            'mediaUrls',
            'assets',
            'movie_language','subtitle_language',
            'seo',
            'clips',
            'download_url_type',
            'page_type'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */



    public function update(VideoRequest $request, int $id)    
{

    $requestData = $request->all();
 
    // Handle movie access and related options
    if ($requestData['access'] == "pay-per-view") {
        $requestData['release_date'] = null;
        $requestData['download_status'] = 0;
    }

    // Handle poster and TV poster URLs
    $requestData['poster_url'] = extractFileNameFromUrl($requestData['poster_url'],'video');
    $requestData['poster_tv_url'] = extractFileNameFromUrl($requestData['poster_tv_url'],'video');
    $requestData['thumbnail_url'] = extractFileNameFromUrl($requestData['thumbnail_url'],'video');
    // Handle SEO Image (if it exists)
    if ($request->hasFile('seo_image')) {
        $image = $request->file('seo_image');

        // Generate a safe filename for the image
        $originalName = $image->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);

        // Store the image and save the filename
        $path = $image->storeAs('public/uploads/seo', $safeName);
        $requestData['seo_image'] = basename($path); // Only store the filename
    }

    // Handle video URL and embedded content
    if ($requestData['video_upload_type'] === 'Embedded') {
        $requestData['video_url_input'] = $request->input('video_embedded');
    } else {
        $videoUrl = $requestData['video_url_input'];
        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
            $requestData['video_url_input'] = $matches[1];
        }
        if ($requestData['video_upload_type'] === 'Local') {
            $requestData['video_url_input'] = extractFileNameFromUrl($requestData['video_file_input'],'video');
        }
    }

    // Handle SEO-related fields
    $requestData['meta_title'] = $request->input('meta_title');

    // Handle meta keywords: convert array to comma-separated string
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
    $requestData['meta_description'] = $request->input('meta_description', '');

    // If empty or undefined, set it to null or some default value (optional)
    if (empty($requestData['meta_description'])) {
        $requestData['meta_description'] = null; // Optional: Set default if it's empty
    }

    // Continue with the other fields
    $requestData['google_site_verification'] = $request->input('google_site_verification');
    $requestData['canonical_url'] = $request->input('canonical_url');
    $requestData['short_description'] = $request->input('short_description');

    // Retrieve the video entry to update
    $data = Video::where('id', $id)->first();

    // Update the video data
    $data->update($requestData);

    if(config('filesystems.active') == 'bunny'){
        $pullBase = rtrim(config('filesystems.bunny_pull_zone'), '/');

        if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
            $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
            $sourceUrl = $pullBase.'/video/video/'.$filename;
            $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
            if ($m3u8) { $data->update(['bunny_video_url' => $m3u8]); }
        }
    }

    if((isset($requestData['enable_download_quality']) && $requestData['enable_download_quality'] == 1)
    || !empty($requestData['quality_video_download_type'])) {
        $this->videoService->storeDownloads($requestData, $id);
    }

    // Update Clips for Video (upsert + delete removed)
    $clipTypes = $request->input('clip_upload_type', []);
    $clipUrls = $request->input('clip_url_input', []);
    $clipFiles = $request->input('clip_file_input', []);
    $clipEmbeds = $request->input('clip_embedded', []);
    $clipIds = $request->input('clip_id', []);
    $clipPosterUrls = $request->input('clip_poster_url', []);
    $clipTvPosterUrls = $request->input('clip_tv_poster_url', []);
    $clipTitles = $request->input('clip_title', []);

    $existingClips = Clip::where('content_id', $data->id)
        ->where('content_type', 'video')
        ->get()->keyBy('id');
    $touched = [];

    if (!empty($clipTypes) && is_array($clipTypes)) {
        $max = max(count($clipTypes), count($clipUrls), count($clipFiles), count($clipEmbeds));
        for ($i = 0; $i < $max; $i++) {
            $type = $clipTypes[$i] ?? null;
            if (!$type) { continue; }
            $url = null;
            if ($type === 'Local') {
                $val = $clipFiles[$i] ?? null;
                if ($val) { $url = extractFileNameFromUrl($val,'video'); }
            } elseif ($type === 'Embedded' || $type === 'Embed') {
                $url = $clipEmbeds[$i] ?? null;
            } else {
                $val = $clipUrls[$i] ?? null;
                if ($val) {
                    if (preg_match('/<iframe[^>]+src=[\'\"][^\'\"]+[\'\"]/i', $val, $m) &&
                        preg_match('/src=[\'\"][^\'\"]+[\'\"]/i', $m[0], $m2)) {
                        $url = trim(str_replace(['src=\"','src=\'','\"','\''], '', $m2[0]));
                    }
                    if (!$url) { $url = $val; }
                }
            }
            if (!$url) { continue; }

            $posterUrl = $clipPosterUrls[$i] ?? null;
            $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
            $title = $clipTitles[$i] ?? null;

            $posterUrl = isset($posterUrl) ? extractFileNameFromUrl($posterUrl,'video') : $posterUrl;
            $tvPosterUrl = isset($tvPosterUrl) ? extractFileNameFromUrl($tvPosterUrl,'video') : $tvPosterUrl;

            $cid = $clipIds[$i] ?? null;
            if ($cid && isset($existingClips[$cid])) {
                $existingClips[$cid]->update([
                    'type' => $type,
                    'url' => $url,
                    'poster_url' => $posterUrl,
                    'tv_poster_url' => $tvPosterUrl,
                    'title' => $title,
                ]);
                $touched[] = (int)$cid;
            } else {
                $new = Clip::create([
                    'content_id' => $data->id,
                    'content_type' => 'video',
                    'type' => $type,
                    'url' => $url,
                    'poster_url' => $posterUrl,
                    'tv_poster_url' => $tvPosterUrl,
                    'title' => $title,
                ]);
                $touched[] = (int)$new->id;
            }
        }
    }
    $toDelete = $existingClips->keys()->diff($touched);
    if ($toDelete->count() > 0) {
        Clip::whereIn('id', $toDelete)->delete();
    }

    // Handle subtitle deletions if any
    if ($request->has('deleted_subtitles')) {
        $deletedIds = explode(',', $request->deleted_subtitles);
        Subtitle::whereIn('id', $deletedIds)->delete();
    }

    // Handle subtitle updates or additions
    if ($request->enable_subtitle == 1 && $request->has('subtitles')) {
        foreach ($request->subtitles as $key => $subtitleData) {
            $languageCode = $subtitleData['language'] ?? null;
            $file = $subtitleData['subtitle_file'] ?? null;
            $isDefault = isset($subtitleData['is_default']) && $subtitleData['is_default'] == 1;

            if (!$languageCode) {
                continue; // Skip if no language code
            }

            $lang_arr = Constant::where('type', 'subtitle_language')->where('value', $languageCode)->first();

            // Check if this subtitle language already exists
            $existingSubtitle = $data->subtitles()->where('language_code', $languageCode)->first();

            if ($file) {
                $extension = strtolower($file->getClientOriginalExtension());

                if (!in_array($extension, ['srt', 'vtt'])) {
                    return back()
                        ->withErrors(["subtitle_file.$key" => 'Only .srt and .vtt files are allowed'])
                        ->withInput();
                }

                $filename = time() . '_' . $key . '_' . str_replace(' ', '_', $file->getClientOriginalName());

                // If it's an SRT file, convert it to VTT
                if ($extension === 'srt') {
                    $srtContent = file_get_contents($file->getRealPath());
                    $vttContent = convertSrtToVtt($srtContent);

                    // Change extension to .vtt
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.vtt';

                    // Store the VTT content
                    Storage::disk('public')->put('subtitles/' . $filename, $vttContent);
                } else {
                    // Store original VTT file
                    $path = $file->storeAs('subtitles', $filename, 'public');
                }

                if ($existingSubtitle) {
                    $existingSubtitle->update([
                        'subtitle_file' => $filename,
                        'is_default' => $isDefault ? 1 : 0,
                        'language' => $lang_arr->name ?? null,
                    ]);
                } else {
                    $data->subtitles()->create([
                        'entertainment_id' => $data->id,
                        'language_code' => $languageCode,
                        'language' => $lang_arr->name ?? null,
                        'subtitle_file' => $filename,
                        'is_default' => $isDefault ? 1 : 0,
                        'type' => 'video',
                    ]);
                }
            }

            // Update existing subtitle without file
            if ($file == null && $existingSubtitle) {
                $existingSubtitle->update([
                    'is_default' => $isDefault ? 1 : 0,
                    'language' => $lang_arr->name ?? null,
                ]);
            }
        }
    }

    // Handle video quality if enabled
    if (!empty($requestData['video_quality_type'])) {
        foreach ($requestData['video_quality_type'] as $key => $type) {
            if ($type === 'Embedded') {
                // Use the embed input field value instead of URL input
                if (isset($requestData['quality_video_embed_input'][$key])) {
                    $requestData['quality_video_url_input'][$key] = $requestData['quality_video_embed_input'][$key];
                }
            } else if (
                $type === 'URL' ||
                $type === 'YouTube' ||
                $type === 'HLS' ||
                $type === 'Vimeo' ||
                $type === 'x265'
            ) {
                // For URL types, extract iframe src if present
                if (isset($requestData['quality_video_url_input'][$key])) {
                    if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $requestData['quality_video_url_input'][$key], $matches)) {
                        $requestData['quality_video_url_input'][$key] = $matches[1];
                    }
                }
            }
        }
    }

    // Mapping video qualities to VideoStreamContentMapping
    if (isset($requestData['enable_quality']) && $requestData['enable_quality'] == 1) {
        $qualityVideoUrl = $requestData['quality_video_url_input'] ?? [];
        $videoQuality = $requestData['video_quality'] ?? [];
        $videoQualityType = $requestData['video_quality_type'] ?? [];
        $qualityVideoFile = $requestData['quality_video'] ?? [];
        $videoQualityUrl = $requestData['video_quality_url'] ?? null; // Single hidden field for Local type

        if (!empty($videoQuality) && (!empty($qualityVideoUrl) || !empty($qualityVideoFile) || !empty($videoQualityUrl)) && !empty($videoQualityType)) {
            // Remove old mappings
            VideoStreamContentMapping::where('video_id', $data->id)->forceDelete();

            foreach ($videoQuality as $index => $videoquality) {
                if ($videoquality != '' && $videoQualityType[$index] != '') {
                    $url = '';
                    if ($videoQualityType[$index] === 'Local') {
                        // Check for video_quality_url array first, then single field, then fallback to array
                        $videoQualityUrlArray = $requestData['video_quality_url'] ?? [];
                        if (isset($videoQualityUrlArray[$index]) && !empty($videoQualityUrlArray[$index])) {
                            $url = extractFileNameFromUrl($videoQualityUrlArray[$index], 'video');
                        } elseif (!empty($videoQualityUrl) && is_string($videoQualityUrl)) {
                            // Fallback to single field for backward compatibility
                            $url = extractFileNameFromUrl($videoQualityUrl, 'video');
                        } elseif (isset($qualityVideoFile[$index]) && !empty($qualityVideoFile[$index])) {
                            $url = extractFileNameFromUrl($qualityVideoFile[$index], 'video');
                        }
                    } elseif ($videoQualityType[$index] === 'Embedded') {
                        $url = $requestData['quality_video_embed'][$index] ?? '';
                    } else {
                        $url = $qualityVideoUrl[$index] ?? '';
                    }

                    if (!empty($url)) {
                        VideoStreamContentMapping::create([
                            'video_id' => $data->id,
                            'url' => $url,
                            'type' => $videoQualityType[$index],
                            'quality' => $videoquality
                        ]);
                    }
                }
            }
        }
    }

    $message = trans('messages.update_form_video');

    // Check if request is AJAX
    if ($request->ajax()) {
        return response()->json([
            'success' => true,
            'message' => $message,
            'redirect' => route('backend.videos.index')
        ]);
    }

    return redirect()->route('backend.videos.index')->with('success', $message);
}



    public function update_status(Request $request, Video $id)
    {
        $id->update(['status' => $request->status]);
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        return response()->json(['status' => true, 'message' => __('messages.status_updated_video')]);
    }

    public function update_is_restricted(Request $request, Video $id)
    {

        $id->update(['is_restricted' => $request->status]);

        clearRelatedCache([], 'video');

        $message='';

        if ($request->status == 1) {
            $message = __('messages.content_added_to_restricted');
        } else {
            $message = __('messages.content_removed_from_restricted');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */

    public function destroy(int $id)    
    {
        $data = Video::where('id', $id)->first();
        $data->delete();
        $message = trans('messages.delete_form_video', ['form' => 'Video']);
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function restore(int $id)    
    {
        $data = Video::withTrashed()->where('id', $id)->first();
        $data->restore();
        $message = trans('messages.restore_form_video', ['form' => 'Video']);
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function forceDelete(int $id)    
    {
        $data = Video::withTrashed()->where('id', $id)->first();
        $data->forceDelete();
        $message = trans('messages.permanent_delete_form_video', ['form' => 'Video']);
        return response()->json(['message' => $message, 'status' => true], 200);
    }


    public function downloadOption(Request $request, int $id)    
    {
        $data = Video::where('id', $id)->with('videoDownloadMappings')->find($id);


        $module_title =  __('messages.download_video') . ' ' .  __('video.singular_title');

        $upload_url_type=Constant::where('type','upload_type')
                                    ->where('status', 1)
                                    ->whereIn('name', ['URL', 'Local'])
                                    ->get();
        $video_quality=Constant::where('type','video_quality')->get();

        return view('video::backend.video.download', compact('data', 'module_title', 'upload_url_type', 'video_quality'));
    }

    public function storeDownloads(Request $request, int $id)    
    {
        $data = $request->all();
        $this->videoService->storeDownloads($data, $id);
        $message = trans('messages.set_download_url_video');

        return redirect()->route('backend.videos.index')->with('success', $message);
    }

    public function generateDescription(Request $request)
    {
        $name = $request->input('name');
        $description = $request->input('description');
        $type=$request->input('type');

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



}