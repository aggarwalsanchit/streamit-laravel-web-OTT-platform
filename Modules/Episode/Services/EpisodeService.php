<?php

namespace Modules\Episode\Services;

use Modules\Episode\Repositories\EpisodeRepositoryInterface;
use Yajra\DataTables\DataTables;

class EpisodeService
{
    protected $episodeRepository;

    public function __construct(EpisodeRepositoryInterface $episodeRepository)
    {
        $this->episodeRepository = $episodeRepository;
    }

    public function getAll()
    {
        return $this->episodeRepository->all();
    }

    public function getById($id)
    {
        return $this->episodeRepository->find($id);
    }



    public function create(array $data)
    {
        $cacheKey = 'episode_';
        clearRelatedCache($cacheKey, 'episode');

        $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];
        $data['video_url_input'] = ($data['video_upload_type'] == 'Local') ? $data['video_file_input'] : $data['video_url_input'];
       $episode = $this->episodeRepository->create($data);

       if(config('filesystems.active') == 'bunny'){
        $pullBase = rtrim(config('filesystems.bunny_pull_zone'), '/');

        if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
            $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
            $sourceUrl = $pullBase.'/tvshow/episode/video/'.$filename;
            $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
            if ($m3u8) { $episode->update(['bunny_video_url' => $m3u8]); }
        }

        if (($data['trailer_url_type'] ?? null) == 'Local' && !empty($data['trailer_url'])) {
            $tfile = basename(parse_url($data['trailer_url'], PHP_URL_PATH));
            $sourceUrl = $pullBase.'/tvshow/episode/video/'.$tfile;
            $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $tfile);
            if ($m3u8) { $episode->update(['bunny_trailer_url' => $m3u8]); }
        }
    }

       if(isset($data['enable_quality']) && $data['enable_quality'] == 1) {
        $this->episodeRepository->saveQualityMappings(
            $episode->id,
            $data['video_quality'],
            $data['quality_video_url_input'],
            $data['video_quality_type'],
            $data['quality_video'],
        );
    }



        return $episode;
    }

    public function update(int $id, array $data)
    {
        $cacheKey = 'episode_'.$id;
        clearRelatedCache($cacheKey, 'episode');

        $data['trailer_url'] = ($data['trailer_url_type'] == 'Local') ? $data['trailer_video'] : $data['trailer_url'];

        $data['video_url_input'] = ($data['video_upload_type'] == 'Local') ? $data['video_file_input'] : $data['video_url_input'];
        $updated =  $this->episodeRepository->update($id, $data);

        if(config('filesystems.active') == 'bunny'){
            $pullBase = rtrim(config('filesystems.bunny_pull_zone'), '/');

            if (($data['video_upload_type'] ?? null) == 'Local' && !empty($data['video_url_input'])) {
                $filename = basename(parse_url($data['video_url_input'], PHP_URL_PATH));
                $sourceUrl = $pullBase.'/tvshow/episode/video/'.$filename;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $filename);
                if ($m3u8) {
                    $updated->update(['bunny_video_url' => $m3u8]);
                }

            }

            if (($data['trailer_url_type'] ?? null) == 'Local' && !empty($data['trailer_url'])) {
                $tfile = basename(parse_url($data['trailer_url'], PHP_URL_PATH));
                $sourceUrl = $pullBase.'/tvshow/episode/video/'.$tfile;
                $m3u8 = bunnyIngestAndGetM3u8($sourceUrl, $tfile);
                if ($m3u8) {
                    $updated->update(['bunny_trailer_url' => $m3u8]);
                }

            }
        }

        return $updated;
    }

    public function delete(int $id)
    {
        $cacheKey = 'episode_'.$id;
        clearRelatedCache($cacheKey, 'episode');


        return $this->episodeRepository->delete($id);
    }

    public function restore($id)
    {
        $cacheKey = 'episode_'.$id;
        clearRelatedCache($cacheKey, 'episode');


        return $this->episodeRepository->restore($id);
    }

    public function forceDelete(int $id)
    {
        $cacheKey = 'episode_'.$id;
        clearRelatedCache($cacheKey, 'episode');


        return $this->episodeRepository->forceDelete($id);
    }

    public function getDataTable(Datatables $datatable, array $filter)
    {
        $query = $this->getFilteredData($filter)->withCount('entertainmentView');
        return $datatable->eloquent($query)
        ->filter(function ($query) {
            if (request()->has('search') && !empty(request()->get('search')['value'])) {
                $searchValue = request()->get('search')['value'];
                $query->where(function ($q) use ($searchValue) {
                    $q->where('name', 'like', '%' . $searchValue . '%')
                      ->orWhereHas('seasondata', function ($seasonQuery) use ($searchValue) {
                          $seasonQuery->where('name', 'like', '%' . $searchValue . '%');
                      })
                      ->orWhereHas('entertainmentdata', function ($entertainmentQuery) use ($searchValue) {
                          $entertainmentQuery->where('name', 'like', '%' . $searchValue . '%');
                      });
                });
            }
        })
        ->editColumn('poster_url', function ($data) {
            $seasonName = optional($data->seasondata)->name;
            $type = 'episode';
            $imageUrl = setBaseUrlWithFileName($data->poster_url, 'image', 'episode');
            return view('components.media-item', ['thumbnail' => $imageUrl, 'name' => $data->name, 'seasonName' => $seasonName, 'type' => $type])->render();
        })

        ->editColumn('entertainment_id', function ($data) {
            return optional($data->entertainmentdata)->name;
        })


        ->editColumn('season_id', function ($data) {
            return optional($data->seasondata)->name;
        })


        ->filterColumn('season_id', function ($data, $keyword) {
            if (!empty($keyword)) {
                $data->whereHas('seasondata', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            }
        })

        ->editColumn('plan_id', function ($data) {
            return $data->access === 'pay-per-view' ? '-' : optional($data->plan)->name ?? '-';
        })


        ->filterColumn('plan_id', function ($query, $keyword) {
            if (!empty($keyword)) {
                $query->whereHas('plan', function ($query) use ($keyword) {
                    $query->where('name', 'like', '%' . $keyword . '%');
                });
            }
        })

        ->filterColumn('entertainment_id', function($query, $keyword) {
            $query->where('entertainment_id', $keyword);
        })

        ->filterColumn('entertainment_id', function ($data, $keyword) {
            if (!empty($keyword)) {
                $data->whereHas('entertainmentdata', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            }
        })


        ->filterColumn('season_id', function($query, $keyword) {
            $query->where('season_id', $keyword);
        })

          ->addColumn('check', function ($data) {
              return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$data->id.'"  name="datatable_ids[]" value="'.$data->id.'" data-type="episode" onclick="dataTableRowCheck('.$data->id.',this)">';
          })
          ->addColumn('action', function ($data) {
              return view('episode::backend.episode.action', compact('data'));
          })
          ->editColumn('status', function ($row) {
            $checked = $row->status ? 'checked="checked"' : ''; // Check if status is active
            $disabled = $row->trashed() ? 'disabled' : ''; // Disable if the record is soft-deleted

            return '
                <div class="form-check form-switch">
                    <input type="checkbox" data-url="' . route('backend.episodes.update_status', $row->id) . '"
                        data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                        id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                </div>
            ';
        })


        ->editColumn('is_restricted', function ($row) {
            $checked = $row->is_restricted ? 'checked' : '';
            $disabled = $row->trashed() ? 'disabled' : '';

            return '
                <div class="form-check form-switch">
                    <input type="checkbox"
                        class="switch-status-change form-check-input"
                        data-id="' . $row->id . '"
                        data-url="' . route('backend.episodes.update_is_restricted', $row->id) . '"
                        data-token="' . csrf_token() . '"
                        ' . $checked . ' ' . $disabled . '>
                </div>';
        })
        ->orderColumn('status', function ($query, $order) {
            $query->orderBy('status', $order);
        })
        ->addColumn('watch_count', function ($data) {
            return $data->entertainment_view_count > 0 ? $data->entertainment_view_count : '-';
        })
        ->orderColumn('watch_count', 'entertainment_view_count $1')
          ->editColumn('updated_at', fn($data) =>formatUpdatedAt($data->updated_at))
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'status', 'check','poster_url','entertainment_id','season_id','plan_id','is_restricted'])
            ->toJson();
    }

    public function getFilteredData($filter)
    {
        $query = $this->episodeRepository->query();

        if (isset($filter['name'])) {
            $query->where('name', $filter['name']);
        }

        if (isset($filter['access'])) {
            $query->where('access', $filter['access']);
        }


        if (isset($filter['plan_id'])) {
            $query->where('plan_id', $filter['plan_id']);
        }

        if (isset($filter['entertainment_id'])) {
            $query->where('entertainment_id', $filter['entertainment_id']);
        }
        if (isset($filter['season_id'])) {
            $query->where('season_id', $filter['season_id']);
        }

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }
        return $query;
    }

    public function getList(array $perPage, string $searchTerm = null)
    {
        return $this->episodeRepository->list($perPage, $searchTerm);
    }

    
    public function storeDownloads(array $data, $id)
    {
        return $this->episodeRepository->storeDownloads($data, $id);
    }


    public function getEpisodeList(int $tvshow_id,int $season_index){

     $curl = curl_init();

     $api_key=gettmdbapiKey();

     curl_setopt_array($curl, array(
       CURLOPT_URL => 'https://api.themoviedb.org/3/tv/'.$tvshow_id.'/season/'.$season_index.'?api_key='.$api_key,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_ENCODING => '',
       CURLOPT_MAXREDIRS => 10,
       CURLOPT_TIMEOUT => 0,
       CURLOPT_FOLLOWLOCATION => true,
       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
       CURLOPT_CUSTOMREQUEST => 'GET',
     ));

     $response = curl_exec($curl);

     curl_close($curl);

     return $response;

    }

    public function getConfiguration(){

        $api_key=gettmdbapiKey();

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://api.themoviedb.org/3/configuration?api_key='.$api_key,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
        ));

       $response = curl_exec($curl);

       curl_close($curl);

       return $response;

    }

    public function  getEpisodeDetails(int $tvshow_id,int $season_id, int $episode_id){

      $curl = curl_init();

     $api_key=gettmdbapiKey();

      curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.themoviedb.org/3/tv/'.$tvshow_id.'/season/'.$season_id.'/episode/'.$episode_id.'?api_key='.$api_key,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
      ));

      $response = curl_exec($curl);

      curl_close($curl);

      return $response;

    }
    
    public function getEpisodevideo(int $tvshow_id,int $season_id, int $episode_id){

       $curl = curl_init();

       $api_key=gettmdbapiKey();

       curl_setopt_array($curl, array(
         CURLOPT_URL => 'https://api.themoviedb.org/3/tv/'.$tvshow_id.'/season/'.$season_id.'/episode/'.$episode_id.'/videos?api_key='.$api_key,
         CURLOPT_RETURNTRANSFER => true,
         CURLOPT_ENCODING => '',
         CURLOPT_MAXREDIRS => 10,
         CURLOPT_TIMEOUT => 0,
         CURLOPT_FOLLOWLOCATION => true,
         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
         CURLOPT_CUSTOMREQUEST => 'GET',
         CURLOPT_HTTPHEADER => array(
           'accept: application/json'
         ),
       ));

       $response = curl_exec($curl);

       curl_close($curl);

       return $response;
    }


}
