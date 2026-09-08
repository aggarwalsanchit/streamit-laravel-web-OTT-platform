<?php

namespace Modules\LiveTV\Services;

use Modules\LiveTV\Repositories\LiveTvChannelRepositoryInterface;
use Modules\LiveTV\Models\TvChannelStreamContentMapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class LiveTvChannelService
{
    protected $liveTvChannelRepository;

    public function __construct(LiveTvChannelRepositoryInterface $liveTvChannelRepository)
    {
        $this->liveTvChannelRepository = $liveTvChannelRepository;
    }

    public function getAll()
    {
        return $this->liveTvChannelRepository->all();
    }

    public function getById(int $id)
    {
        return $this->liveTvChannelRepository->find($id);
    }

    public function create(array $data, Request $request)
    {
        $cacheKey = 'livetv_channel_list';
        Cache::forget($cacheKey);

        if ($request->type === 't_url') {
            $data['stream_type'] = $request->input('stream_type');
            $data['server_url'] = $request->input('server_url');
            $data['server_url1'] = $request->input('server_url1');
            $data['embedded'] = null;
        } else if ($request->type === 't_embedded') {
            $data['stream_type'] = $request->input('stream_type');
            $data['server_url'] = null;
            $data['server_url1'] = null;
            $data['embedded'] = $request->input('embedded');
        }

        $liveTvChannel = $this->liveTvChannelRepository->create($data);

        if ($request->hasFile('poster_url')) {
            $file = $request->file('poster_url');
            StoreMediaFile($liveTvChannel, $file, 'poster_url');

            $bannerData = $this->liveTvChannelRepository->find($liveTvChannel->id);
            $liveTvChannel->poster_url = $bannerData->poster_url;
            $liveTvChannel->save();
        }

        if (!empty($liveTvChannel) && !empty($data['stream_type'])) {
            $mappingstream = [
                'tv_channel_id' => $liveTvChannel->id,
                'type' => $data['type'],
                'stream_type' => $data['stream_type'],
                'embedded' => $data['embedded'],
                'server_url' => $data['server_url'],
                'server_url1' => $data['server_url1'],
            ];

            TvChannelStreamContentMapping::create($mappingstream);
        }

        return $liveTvChannel;
    }
    public function update(int $id, array $data)
    {
        $cacheKey = 'livetv_channel_list';
        Cache::forget($cacheKey);
        
        // Clear LiveTV dashboard cache when channel is updated
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        
        return $this->liveTvChannelRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $cacheKey = 'livetv_channel_list';
        Cache::forget($cacheKey);
        
        // Clear LiveTV dashboard cache when channel is deleted
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        
        return $this->liveTvChannelRepository->delete($id);
    }

    public function restore(int $id)
    {
        $cacheKey = 'livetv_channel_list';
        Cache::forget($cacheKey);
        
        // Clear LiveTV dashboard cache when channel is restored
        if (function_exists('clearLiveTvDashboardCache')) {
            clearLiveTvDashboardCache();
        }
        
        return $this->liveTvChannelRepository->restore($id);
    }

    public function forceDelete(int $id)    
    {
        $cacheKey = 'livetv_channel_list';
        Cache::forget($cacheKey);
        return $this->liveTvChannelRepository->forceDelete($id);
    }

}
