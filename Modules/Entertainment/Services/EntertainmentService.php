<?php

namespace Modules\Entertainment\Services;

use Modules\Entertainment\Repositories\EntertainmentRepositoryInterface;
use  Modules\Genres\Repositories\GenreRepositoryInterface;
use Yajra\DataTables\DataTables;
use App\Models\Clip;
use Modules\Season\Models\Season;
use Modules\SEO\Models\Seo;
use Modules\Constant\Models\Constant;
use Modules\NotificationTemplate\Jobs\SendBulkNotification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EntertainmentService
{
    protected $entertainmentRepository;
    protected $genresRepository;

    public function __construct( EntertainmentRepositoryInterface $entertainmentRepository, GenreRepositoryInterface $genresRepository)
    {
        $this->entertainmentRepository = $entertainmentRepository;
        $this->genresRepository = $genresRepository;
    }

    public function getAll()
    {
        return $this->entertainmentRepository->all();
    }

    public function getById(int $id)
    {
        return $this->entertainmentRepository->find($id);
    }

    public function create(array $data)
    {

        $cacheKey1 = 'movie_';
        $cacheKey2 = 'tvshow_';

        clearRelatedCache([$cacheKey1, $cacheKey2], 'entertainment');

        $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];



        if($data['type']=='movie'){

            $data['video_url_input'] = ($data['video_upload_type'] == 'Local') ? $data['video_file_input'] : $data['video_url_input'];

        }else{
            $data['video_url_input']=null;
        }


        $entertainment = $this->entertainmentRepository->create($data);
        if(config('filesystems.active') == 'bunny'){
            $pullBase = rtrim(config('filesystems.bunny_pull_zone'), '/');

            if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
                $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
                $sourceUrl = $pullBase.'/movie/video/'.$filename;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
                if ($m3u8) { $entertainment->update(['bunny_video_url' => $m3u8]); }
            }

            if (($data['trailer_url_type'] ?? null) == 'Local' && !empty($data['trailer_url'])) {
                $tfile = basename(parse_url($data['trailer_url'], PHP_URL_PATH));
                $type = ($entertainment->type == 'movie') ? 'movie' : 'tvshow';
                $sourceUrl = $pullBase . '/' . $type . '/video/' . $tfile;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $tfile);
                if ($m3u8) { $entertainment->update(['bunny_trailer_url' => $m3u8]); }
            }
        }

        if (!empty($data['genres'])) {
            $this->entertainmentRepository->saveGenreMappings($data['genres'], $entertainment->id);
        }
        if (!empty($data['countries'])) {
            $this->entertainmentRepository->saveCountryMappings($data['countries'], $entertainment->id);
        }

        if (!empty($data['actors'])) {
            $this->entertainmentRepository->saveTalentMappings($data['actors'], $entertainment->id);
        }

        if (!empty($data['directors'])) {
            $this->entertainmentRepository->saveTalentMappings($data['directors'], $entertainment->id);
        }

        if (isset($data['enable_quality']) && $data['enable_quality'] == 1) {
            // Check if the keys are set to avoid undefined key errors
            $videoQuality = isset($data['video_quality']) ? $data['video_quality'] : [];
            $qualityVideoUrlInput = isset($data['quality_video_url_input']) ? $data['quality_video_url_input'] : [];
            $videoQualityType = isset($data['video_quality_type']) ? $data['video_quality_type'] : [];
            $qualityVideo = isset($data['quality_video']) ? $data['quality_video'] : [];

            $this->entertainmentRepository->saveQualityMappings(
                $entertainment->id,
                $videoQuality,
                $qualityVideoUrlInput,
                $videoQualityType,
                $qualityVideo
            );
        }

        // Handle Clips storage
        $clipTypes = isset($data['clip_upload_type']) && is_array($data['clip_upload_type']) ? $data['clip_upload_type'] : [];
        $clipUrls = isset($data['clip_url_input']) && is_array($data['clip_url_input']) ? $data['clip_url_input'] : [];
        $clipFiles = isset($data['clip_file_input']) && is_array($data['clip_file_input']) ? $data['clip_file_input'] : [];
        $clipEmbeds = isset($data['clip_embedded']) && is_array($data['clip_embedded']) ? $data['clip_embedded'] : [];
        $clipPosterUrls = isset($data['clip_poster_url']) && is_array($data['clip_poster_url']) ? $data['clip_poster_url'] : [];
        $clipTvPosterUrls = isset($data['clip_tv_poster_url']) && is_array($data['clip_tv_poster_url']) ? $data['clip_tv_poster_url'] : [];
        $clipTitles = isset($data['clip_title']) && is_array($data['clip_title']) ? $data['clip_title'] : [];

          if (!empty($clipTypes)) {
            $max = max(
                count($clipTypes),
                count($clipUrls),
                count($clipFiles),
                count($clipEmbeds),
                count($clipPosterUrls),
                count($clipTvPosterUrls),
                count($clipTitles)
            );

            for ($i = 0; $i < $max; $i++) {
                $type = $clipTypes[$i] ?? null;
                if (!$type) { continue; }

                $url = null;
                if ($type === 'Local') {
                    $val = $clipFiles[$i] ?? null;
                    if ($val) {
                        $url = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,$entertainment->type) : $val;
                    }
                } elseif ($type === 'Embedded' || $type === 'Embed') {
                    $url = $clipEmbeds[$i] ?? null;
                } else {
                    $val = $clipUrls[$i] ?? null;
                    if ($val) {
                        if (preg_match('/<iframe[^>]+src=[\'\"][^\'\"]+[\'\"]/i', $val, $m)) {
                            if (preg_match('/src=[\'\"][^\'\"]+[\'\"]/i', $m[0], $m2)) {
                                $url = trim(str_replace(['src="','src=\'','"','\''], '', $m2[0]));
                            }
                        }
                        if (!$url) { $url = $val; }
                    }
                }

                if ($url) {
                    $posterUrl = $clipPosterUrls[$i] ?? null;
                    $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                    $title = $clipTitles[$i] ?? null;

                    $posterUrl = isset($posterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($posterUrl,$entertainment->type) : $posterUrl;
                    $tvPosterUrl = isset($tvPosterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($tvPosterUrl,$entertainment->type) : $tvPosterUrl;

                    Clip::create([
                        'content_id' => $entertainment->id,
                        'content_type' => $entertainment->type == 'movie' ? 'movie' : 'tv_show',
                        'type' => $type,
                        'url' => $url,
                        'poster_url' => $posterUrl,
                        'tv_poster_url' => $tvPosterUrl,
                        'title' => $title,
                    ]);
                }
            }
        }


        return $entertainment;
    }


    public function update(int $id, array $data)
    {
        $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

            $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];

            $data['video_url_input'] = ($data['video_upload_type'] == 'Local') ? $data['video_file_input'] : $data['video_url_input'];
          }else{

            $cacheKey = 'tvshow_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

            $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];

            }

        $updated = $this->entertainmentRepository->update($id, $data);
        
        if(config('filesystems.active') == 'bunny'){
            $pullBase = rtrim(config('filesystems.bunny_pull_zone'), '/');  

            if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
                $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
                $sourceUrl = $pullBase.'/movie/video/'.$filename;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
                if ($m3u8) {
                    $entertainment->update(['bunny_video_url' => $m3u8]);
                }

            }

            if (($data['trailer_url_type'] ?? null) == 'Local' && !empty($data['trailer_url'])) {
                $tfile = basename(parse_url($data['trailer_url'], PHP_URL_PATH));
                $type = ($entertainment->type == 'movie') ? 'movie' : 'tvshow';
                $sourceUrl = $pullBase . '/' . $type . '/video/' . $tfile;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $tfile);
                if ($m3u8) {
                    $entertainment->update(['bunny_trailer_url' => $m3u8]);
                }

            }
        }

        // Handle Clips update (upsert + delete removed)
        $clipIds = isset($data['clip_id']) && is_array($data['clip_id']) ? $data['clip_id'] : [];
        $clipTypes = isset($data['clip_upload_type']) && is_array($data['clip_upload_type']) ? $data['clip_upload_type'] : [];
        $clipUrls = isset($data['clip_url_input']) && is_array($data['clip_url_input']) ? $data['clip_url_input'] : [];
        $clipFiles = isset($data['clip_file_input']) && is_array($data['clip_file_input']) ? $data['clip_file_input'] : [];
        $clipEmbeds = isset($data['clip_embedded']) && is_array($data['clip_embedded']) ? $data['clip_embedded'] : [];
        $clipPosterUrls = isset($data['clip_poster_url']) && is_array($data['clip_poster_url']) ? $data['clip_poster_url'] : [];
        $clipTvPosterUrls = isset($data['clip_tv_poster_url']) && is_array($data['clip_tv_poster_url']) ? $data['clip_tv_poster_url'] : [];
        $clipTitles = isset($data['clip_title']) && is_array($data['clip_title']) ? $data['clip_title'] : [];

        $existingClips = Clip::where('content_id', $entertainment->id)
            ->where('content_type', $entertainment->type == 'movie' ? 'movie' : 'tv_show')
            ->get()
            ->keyBy('id');

        $touchedIds = [];
        if (!empty($clipTypes)) {
            $max = max(
                count($clipTypes),
                count($clipUrls),
                count($clipFiles),
                count($clipEmbeds),
                count($clipPosterUrls),
                count($clipTvPosterUrls),
                count($clipTitles)
            );

            for ($i = 0; $i < $max; $i++) {
                $typeClip = $clipTypes[$i] ?? null;
                if (!$typeClip) { continue; }

                $url = null;
                if ($typeClip === 'Local') {
                    $val = $clipFiles[$i] ?? null;
                    if ($val) {
                        $url = function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($val,$entertainment->type) : $val;
                    }
                } elseif ($typeClip === 'Embedded' || $typeClip === 'Embed') {
                    $url = $clipEmbeds[$i] ?? null;
                } else {
                    $val = $clipUrls[$i] ?? null;
                    if ($val) {
                        if (preg_match('/<iframe[^>]+src=[\'\"][^\'\"]+[\'\"]/i', $val, $m)) {
                            if (preg_match('/src=[\'\"][^\'\"]+[\'\"]/i', $m[0], $m2)) {
                                $url = trim(str_replace(['src=\"','src=\'','\"','\''], '', $m2[0]));
                            }
                        }
                        if (!$url) { $url = $val; }
                    }
                }

                if (!$url) { continue; }

                $existingId = $clipIds[$i] ?? null;
                if ($existingId && isset($existingClips[$existingId])) {
                    $posterUrl = $clipPosterUrls[$i] ?? null;
                    $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                    $title = $clipTitles[$i] ?? null;

                    $posterUrl = isset($posterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($posterUrl,$entertainment->type) : $posterUrl;
                    $tvPosterUrl = isset($tvPosterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($tvPosterUrl,$entertainment->type) : $tvPosterUrl;

                    $existingClips[$existingId]->update([
                        'type' => $typeClip,
                        'url' => $url,
                        'poster_url' => $posterUrl,
                        'tv_poster_url' => $tvPosterUrl,
                        'title' => $title,
                    ]);
                    $touchedIds[] = (int)$existingId;
                } else {
                    $posterUrl = $clipPosterUrls[$i] ?? null;
                    $tvPosterUrl = $clipTvPosterUrls[$i] ?? null;
                    $title = $clipTitles[$i] ?? null;

                    $posterUrl = isset($posterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($posterUrl,$entertainment->type) : $posterUrl;
                    $tvPosterUrl = isset($tvPosterUrl) && function_exists('extractFileNameFromUrl') ? extractFileNameFromUrl($tvPosterUrl,$entertainment->type) : $tvPosterUrl;

                    $new = Clip::create([
                        'content_id' => $entertainment->id,
                        'content_type' => $entertainment->type == 'movie' ? 'movie' : 'tv_show',
                        'type' => $typeClip,
                        'url' => $url,
                        'poster_url' => $posterUrl,
                        'tv_poster_url' => $tvPosterUrl,
                        'title' => $title,
                    ]);
                    $touchedIds[] = (int)$new->id;
                }
            }
        }

        $toDelete = $existingClips->keys()->diff($touchedIds);
        if ($toDelete->count() > 0) {
            Clip::whereIn('id', $toDelete)->delete();
        }

        if ($entertainment->type == 'tvshow') {
            $this->syncSeasonsAccessWithTvShow($id, $data);
        }

        return $updated;
    }

    /**
     * Sync access settings from TV show to all its seasons
     *
     * @param int $tvshowId
     * @param array $data
     * @return void
     */
    protected function syncSeasonsAccessWithTvShow(int $tvshowId, array $data)
    {
        $accessFields = [
            'access' => $data['movie_access'] ?? null,
            'plan_id' => $data['plan_id'] ?? null,
            'price' => $data['price'] ?? null,
            'purchase_type' => $data['purchase_type'] ?? null,
            'access_duration' => $data['access_duration'] ?? null,
            'discount' => $data['discount'] ?? null,
            'available_for' => $data['available_for'] ?? null,
        ];

        $accessFields = array_filter($accessFields, function($value) {
            return $value !== null;
        });
        if (!empty($accessFields)) {
            Season::where('entertainment_id', $tvshowId)
                ->update($accessFields);
            
            clearRelatedCache([], 'season');
        }
    }

    public function delete(int $id)
    {
          $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

          }else{

            $cacheKey = 'tvshow_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

            }

        return $this->entertainmentRepository->delete($id);
    }

    public function restore(int $id)
    {
        $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

          }else{

            $cacheKey = 'tvshow_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

          }

        return $this->entertainmentRepository->restore($id);
    }

    public function forceDelete(int $id)
    {
        $entertainment = $this->entertainmentRepository->find($id);

        if($entertainment->type=='movie'){

            $cacheKey = 'movie_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

        } else {

            $cacheKey = 'tvshow_'.$id;
            clearRelatedCache($cacheKey, 'entertainment');

            }

        return $this->entertainmentRepository->forceDelete($id);
    }


 public function getDataTable(Datatables $datatable, array $filter, string $type)
    {
        $query = $this->getFilteredData($filter, $type)
            ->withCount([
                'entertainmentLike' => function ($query) use ($type) {
                    $query->where('is_like', 1)->where('type', $type);
                },
                'entertainmentView' => function ($query) {
                    // Add custom logic here if needed
                }
            ]);

    return $datatable->eloquent($query)
        ->editColumn('thumbnail_url', function ($data) {
            $genres = $this->entertainmentRepository->movieGenres($data->id);
            $countries = $this->entertainmentRepository->moviecountries($data->id);
            $type = 'movie';
            $releaseDate = $data->release_date ? formatDate($data->release_date) : '';
            $imageUrl = setBaseUrlWithFileName($data->thumbnail_url, 'image', 'movie');
            return view('components.media-item', [
                'thumbnail' => $imageUrl,
                'name' => $data->name,
                'genre' => implode(', ', $genres->toArray()),
                'country' => implode(', ', $countries->toArray()),
                'releaseDate' => $releaseDate,
                'type' => $type
            ])->render();
        })
        ->addColumn('like_count', function ($data) {
            return $data->entertainment_like_count > 0 ? $data->entertainment_like_count : '-';
        })
        ->orderColumn('like_count', 'entertainment_like_count $1')
        ->addColumn('watch_count', function ($data) {
            return $data->entertainment_view_count > 0 ? $data->entertainment_view_count : '-';
        })
        ->orderColumn('watch_count', 'entertainment_view_count $1')
        ->filterColumn('thumbnail_url', function ($query, $keyword) {
            if (!empty($keyword)) {
                $query->where(function($q) use ($keyword) {
                    // Search by movie name
                    $q->where('name', 'like', '%' . $keyword . '%')
                      // Search by genre names
                      ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($keyword) {
                          $genreQuery->where('name', 'like', '%' . $keyword . '%');
                      })
                      // Search by language
                      ->orWhere('language', 'like', '%' . $keyword . '%')
                      // Search by access type (paid, free, pay-per-view)
                      ->orWhere('movie_access', 'like', '%' . $keyword . '%')
                      // Search by plan name
                      ->orWhereHas('plan', function ($planQuery) use ($keyword) {
                          $planQuery->where('name', 'like', '%' . $keyword . '%');
                      });
                });
            }
        })
        ->editColumn('plan_id', function ($data) {
            return $data->movie_access === 'pay-per-view' ? '-' : optional($data->plan)->name ?? '-';
        })
        ->filterColumn('plan_id', function ($query, $keyword) {
            if (!empty($keyword)) {
                $query->whereHas('plan', function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                });
            }
        })
        ->addColumn('check', function ($data) {
            return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-' . $data->id . '" name="datatable_ids[]" value="' . $data->id . '" data-type="entertainment" onclick="dataTableRowCheck(' . $data->id . ',this)">';
        })
        ->addColumn('action', function ($data) {
           return view('entertainment::backend.entertainment.action', compact('data'));
        })
        ->editColumn('status', function ($row) {
            $checked = $row->status ? 'checked="checked"' : '';
            $disabled = $row->trashed() ? 'disabled' : '';

            return '
                <div class="form-check form-switch">
                    <input type="checkbox" data-url="' . route('backend.entertainments.update_status', $row->id) . '"
                        data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                        id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                </div>';
        })
        ->editColumn('is_restricted', function ($row) {
            $checked = $row->is_restricted ? 'checked' : '';
            $disabled = $row->trashed() ? 'disabled' : '';

            return '
                <div class="form-check form-switch">
                    <input type="checkbox"
                        class="switch-status-change form-check-input"
                        data-id="' . $row->id . '"
                        data-url="' . route('backend.entertainments.update_is_restricted', $row->id) . '"
                        data-token="' . csrf_token() . '"
                        ' . $checked . ' ' . $disabled . '>
                </div>';
        })
        ->editColumn('updated_at', fn($data) => formatUpdatedAt($data->updated_at))
        ->orderColumns(['id'], '-:column $1')
        ->rawColumns(['action', 'status', 'check', 'thumbnail_url', 'is_restricted'])
        ->toJson();
}
    public function getFilteredData(array $filter, string $type)
    {
        $query = $this->entertainmentRepository->query();

        if($type!=null){

            $query = $query->where('type',$type);
        }

        if (isset($filter['movie_name']) && !empty($filter['movie_name'])) {
            $searchTerm = $filter['movie_name'];
            $query->where(function($q) use ($searchTerm) {
                // Search by movie name
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  // Search by genre names
                  ->orWhereHas('entertainmentGenerMappings.genre', function ($genreQuery) use ($searchTerm) {
                      $genreQuery->where('name', 'like', '%' . $searchTerm . '%');
                  })
                  // Search by language
                  ->orWhere('language', 'like', '%' . $searchTerm . '%')
                  // Search by access type (paid, free, pay-per-view)
                  ->orWhere('movie_access', 'like', '%' . $searchTerm . '%')
                  // Search by plan name
                  ->orWhereHas('plan', function ($planQuery) use ($searchTerm) {
                      $planQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }


        if (isset($filter['plan_id']) && !empty($filter['plan_id'])) {
            $query->where('plan_id', $filter['plan_id']);
        }

        if (isset($filter['movie_access']) && !empty($filter['movie_access'])) {
            $query->where('movie_access', $filter['movie_access']);
        }

        if (isset($filter['language']) && !empty($filter['language'])) {
            $query->where('language', $filter['language']);
        }

        if (isset($filter['gener']) && !empty($filter['gener'])) {
            $query->whereHas('entertainmentGenerMappings', function ($q) use ($filter) {
                $q->where('genre_id', $filter['gener']);
            });
        }

        if (isset($filter['actor_id']) && !empty($filter['actor_id'])) {
            $query->whereHas('entertainmentTalentMappings', function ($q) use ($filter) {
                $q->where('talent_id', $filter['actor_id'])
                  ->whereHas('talentprofile', function ($subQuery) {
                      $subQuery->where('type', 'actor');
                  });
            });
        }

        if (isset($filter['director_id']) && !empty($filter['director_id'])) {
            $query->whereHas('entertainmentTalentMappings', function ($q) use ($filter) {
                $q->where('talent_id', $filter['director_id'])
                  ->whereHas('talentprofile', function ($subQuery) {
                      $subQuery->where('type', 'director');
                  });
            });
        }

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }

        return $query;
    }

    public function storeDownloads(array $data, int $id)
    {
        return $this->entertainmentRepository->storeDownloads($data, $id);
    }



    public function getEntertainmentList(array $perPage, string $searchTerm = null)
    {
        return $this->entertainmentRepository->list($perPage, $searchTerm);
    }

    /**
     * Store entertainment with all business logic
     * 
     * @param Request $request
     * @return array Returns ['entertainment' => $entertainment, 'message' => $message]
     * @throws \Exception
     */
    public function storeEntertainment(Request $request)
    {
        // Get all request data
        $data = $request->all();

        // Handle movie access and related options
        if (isset($data['movie_access']) && $data['movie_access'] == "pay-per-view") {
            $data['download_status'] = 0;
            $data['release_date'] = null;
        }

        // Validate SEO meta title uniqueness
        if (isset($data['meta_title']) && Seo::where('meta_title', $data['meta_title'])->exists()) {
            throw new \Exception('This Meta Title is already taken. Please choose a different one.');
        }

        // Handle SEO image upload and store only the filename
        if ($request->hasFile('seo_image')) {
            $image = $request->file('seo_image');

            // Generate a safe filename for the image
            $originalName = $image->getClientOriginalName();
            $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);

            // Store the image and save only the filename
            $path = $image->storeAs('public/uploads/seo', $safeName);
            $data['seo_image'] = 'storage/uploads/seo/' . basename($path);
        }

        // Handle video quality types
        if (!empty($data['video_quality_type'])) {
            foreach ($data['video_quality_type'] as $key => $type) {
                if ($type === 'Embedded') {
                    // Handle embedded video URLs
                    if (isset($data['quality_video_embed_input'][$key])) {
                        $data['quality_video_url_input'][$key] = $data['quality_video_embed_input'][$key];
                    }
                } else {
                    // Handle URL types like YouTube, Vimeo, etc.
                    if (isset($data['quality_video_url_input'][$key])) {
                        if (preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $data['quality_video_url_input'][$key], $matches)) {
                            $data['quality_video_url_input'][$key] = $matches[1];
                        }
                    }
                }
            }
        }

        // Handle iframe content differently for video URL
        $videoType = $data['video_upload_type'] ?? null;
        $videoUrl = $data['video_url_input'] ?? null;

        if ($videoType === 'Embedded') {
            $data['video_url_input'] = $request->input('video_embedded');
        } else {
            if ($videoUrl && preg_match('/<iframe[^>]+src=[\'"]([^\'"]+)[\'"]/i', $videoUrl, $matches)) {
                $data['video_url_input'] = $matches[1];
            }
        }

        // Handle trailer embed code
        if (isset($request->trailer_url_type) && $request->trailer_url_type === 'Embedded') {
            $data['trailer_url'] = $request->input('trailer_embedded');
        }

        // Handle SEO fields
        $data['meta_title'] = $request->input('meta_title');

        // Convert the array of meta keywords into a comma-separated string
        if (isset($data['meta_keywords']) && is_array($data['meta_keywords'])) {
            $data['meta_keywords'] = implode(',', $data['meta_keywords']);
        } elseif (!isset($data['meta_keywords'])) {
            $data['meta_keywords'] = '';
        }

        $data['meta_description'] = $request->input('meta_description', '');
        $data['google_site_verification'] = $request->input('google_site_verification');
        $data['canonical_url'] = $request->input('canonical_url');
        $data['short_description'] = $request->input('short_description');

        // Handle other image uploads (e.g., thumbnails, posters, etc.)
        if (function_exists('extractFileNameFromUrl')) {
            $data['thumbnail_url'] = !empty($data['tmdb_id']) ? $data['thumbnail_url'] : extractFileNameFromUrl($data['thumbnail_url'] ?? '', $data['type'] ?? 'movie');
            $data['poster_url'] = !empty($data['tmdb_id']) ? $data['poster_url'] : extractFileNameFromUrl($data['poster_url'] ?? '', $data['type'] ?? 'movie');
            $data['poster_tv_url'] = !empty($data['tmdb_id']) ? $data['poster_tv_url'] : extractFileNameFromUrl($data['poster_tv_url'] ?? '', $data['type'] ?? 'movie');
        }

        // Process additional image/video fields (e.g., IMDb rating, trailer, etc.)
        if (isset($data['IMDb_rating'])) {
            $data['IMDb_rating'] = round($data['IMDb_rating'], 1);
        }

        // Create the entertainment record
        $entertainment = $this->create($data);

        // Handle downloads
        if ((isset($data['enable_download_quality']) && $data['enable_download_quality'] == 1)
            || !empty($data['quality_video_download_type'] ?? [])) {
            $this->storeDownloads($data, $entertainment->id);
        }

        // Handle multiple subtitles
        if ($request->has('enable_subtitle') && $request->enable_subtitle && $request->has('subtitles')) {
            $this->storeSubtitles($request, $entertainment);
        }

        // Send notifications
        $this->sendNotifications($entertainment, $data);

        // Generate success message
        $message = $entertainment->type == 'movie' ?
            trans('messages.create_form_movie') : trans('messages.create_form_tvshow');

        return [
            'entertainment' => $entertainment,
            'message' => $message
        ];
    }

    /**
     * Store subtitles for entertainment
     * 
     * @param Request $request
     * @param \Modules\Entertainment\Models\Entertainment $entertainment
     * @return void
     * @throws \Exception
     */
    protected function storeSubtitles(Request $request, $entertainment)
    {
        foreach ($request->file('subtitles') as $index => $subtitleInput) {
            $language = $request->input("subtitles.$index.language");
            $file = $subtitleInput['subtitle_file'] ?? null;
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
                    if (function_exists('convertSrtToVtt')) {
                        $vttContent = convertSrtToVtt($srtContent);
                        $filename = pathinfo($filename, PATHINFO_FILENAME) . '.vtt';
                        Storage::disk('public')->put('subtitles/' . $filename, $vttContent);
                    } else {
                        throw new \Exception('convertSrtToVtt function is not available');
                    }
                } else {
                    // Store original VTT file
                    $file->storeAs('subtitles', $filename, 'public');
                }

                $entertainment->subtitles()->create([
                    'entertainment_id' => $entertainment->id,
                    'language_code' => $language,
                    'language' => $lang_arr->name ?? null,
                    'subtitle_file' => $filename,
                    'is_default' => $isDefault ? 1 : 0,
                    'type' => 'movie',
                ]);
            }
        }
    }

    /**
     * Send notifications for entertainment
     * 
     * @param \Modules\Entertainment\Models\Entertainment $entertainment
     * @param array $data
     * @return void
     */
    protected function sendNotifications($entertainment, array $data)
    {
        // Send notification for new movie/TV show added only when release date is today or earlier
        if (isset($data['status']) && $data['status'] == 1) {
            $releaseDate = $entertainment->release_date ? Carbon::parse($entertainment->release_date)->startOfDay() : null;
            $today = now()->startOfDay();

            if (!$releaseDate || $releaseDate->lessThanOrEqualTo($today)) {
                $notificationType = $entertainment->type == 'movie' ? 'movie_add' : 'tv_show_add';
                $notificationData = [
                    'notification_type' => $notificationType,
                    'id' => $entertainment->id,
                    'release_date' => $entertainment->release_date,
                ];
                if ($entertainment->type == 'movie') {
                    $notificationData['movie_name'] = $entertainment->name;
                } else {
                    $notificationData['tvshow_name'] = $entertainment->name;
                }
                SendBulkNotification::dispatch($notificationData)->onQueue('notifications');
            }

            // Upcoming notification when release date is in the configured upcoming window
            $upcomingDays = (int) (setting('upcoming') ?? 0);
            $upcomingThreshold = $today->copy()->addDays($upcomingDays)->endOfDay();
            $isUpcomingWindow = $releaseDate && $releaseDate->greaterThan($today) && $releaseDate->lessThanOrEqualTo($upcomingThreshold);

            if ($isUpcomingWindow) {
                $daysRemaining = $today->diffInDays($releaseDate, false);
                $upcomingData = [
                    'notification_type' => 'upcoming',
                    'id' => $entertainment->id,
                    'name' => $entertainment->name,
                    'content_type' => $entertainment->type,
                    'release_date' => $entertainment->release_date,
                    'description' => $entertainment->description,
                    'days' => $daysRemaining,
                    'days_remaining' => $daysRemaining,
                    'posterimage' => $entertainment->poster_url ?? null,
                ];
                SendBulkNotification::dispatch($upcomingData)->onQueue('notifications');
            }
        }
    }

}
