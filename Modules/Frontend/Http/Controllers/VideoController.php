<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\Like;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Video\Models\Video;
use Modules\Video\Transformers\VideoDetailResource;
use Illuminate\Support\Facades\Crypt;
use Modules\Banner\Models\Banner;
use Modules\Banner\Transformers\Backend\SliderResourceV3;
use Illuminate\Support\Facades\Auth;


class VideoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function videoList()
    {
        $user_id = auth()->id();

        // Get featured videos for slider
        $featured_videos = Banner::where('banner_for', 'video')
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();
        $sliders = $featured_videos->map(function ($banner) use ($user_id) {
            return (new SliderResourceV3($banner, $user_id))->toArray(request());
        })->values()->all();

        return view('frontend::video', compact('sliders'));
    }

    public function videoDetails(Request $request, string $id)
    {
        $user_id = Auth::id();
        $profile_id = $request->profile_id ?? 'guest';
        $cacheKey = "video_details_{$id}_user_{$user_id}_profile_{$profile_id}";
        $continue_watch = $request->boolean('continue_watch', false);
        $is_search = $request->boolean('is_search', false);

        $videoGuard = Video::where('slug', $id)->first();
        if (empty($videoGuard) || (int) ($videoGuard->status) !== 1 || $videoGuard->deleted_at !== null) {
            return redirect()->route('user.login');
        } 
        
        if($videoGuard->is_restricted == 1){
            $currentProfile = getCurrentProfileSession('is_child_profile');
            if($currentProfile == 1){
                return redirect()->route('user.login');
            }
        }

        if($videoGuard->release_date != null && $videoGuard->release_date->isFuture()){
            return redirect()->route('user.login');
        }

        $data = cacheApiResponse($cacheKey, 10, function () use ($id, $user_id) {

            $video = Video::with([
                    'VideoStreamContentMappings',
                    'plan',
                    'clips',
                ])
                ->where('slug','=', $id)
                ->first();

            if (!$video) {
                abort(404, 'Video not found.');
            }

            if (!empty($video->trailer_url) && $video->trailer_url_type !== 'Local') {
                $video->trailer_url = Crypt::encryptString($video->trailer_url);
            }

            if (!empty($video->video_url_input) && $video->video_upload_type !== 'Local') {
                $video->video_url_input = Crypt::encryptString($video->video_url_input);
            }

            if ($user_id) {
                $video->continue_watch = ContinueWatch::where([
                    ['entertainment_id', $video->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'video'],
                ])->first();

                $profile_id = getCurrentProfile($user_id, request());
                $video->is_watch_list = WatchList::where([
                    ['entertainment_id', $video->id],
                    ['user_id', $user_id],
                    ['type', 'video'],
                    ['profile_id', $profile_id],
                ])->exists();

                $video->is_likes = Like::where([
                    ['entertainment_id', $video->id],
                    ['user_id', $user_id],
                    ['is_like', 1],
                    ['type', 'video'],
                ])->exists();

                $video->is_download = EntertainmentDownload::where([
                    ['entertainment_id', $video->id],
                    ['user_id', $user_id],
                    ['entertainment_type', 'video'],
                    ['is_download', 1],
                ])->exists();
            }

            $data = (new VideoDetailResource($video))->toArray(request());
            $data['type'] = 'video';
            $data['seoData'] = (object) [
                "seo_image" => $video->seo_image,
                "google_site_verification" => $video->google_site_verification,
                "canonical_url" => $video->canonical_url,
                "short_description" => $video->short_description,
                "meta_title" => $video->meta_title,
                "meta_keywords" => $video->meta_keywords,
            ];

            return $data;
        });


        $entertainmentType = 'video';
        $entertainment = $data['data']['seoData'];

        return view('frontend::video_detail', compact('data', 'entertainment', 'continue_watch'));
    }



    public function comingSoonList()
    {
        return view('frontend::comingsoon');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        //
    }
}
