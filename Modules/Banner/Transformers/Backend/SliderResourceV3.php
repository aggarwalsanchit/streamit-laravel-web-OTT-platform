<?php

namespace Modules\Banner\Transformers\Backend;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Entertainment\Models\Entertainment;
use Modules\LiveTV\Models\LiveTvChannel;
use Modules\Entertainment\Transformers\Backend\CommonContentResourceV3;
use Modules\LiveTV\Transformers\LiveTvChannelResource;
use Modules\Entertainment\Models\Watchlist;
use Modules\Video\Models\Video;
use Modules\Video\Transformers\Backend\VideoResourceV3;
use Modules\Season\Models\Season;
use Modules\Episode\Models\Episode;

class SliderResourceV3 extends JsonResource
{
    private $userId;
    private $profileId;
    private $isRestricted;
    private $isChildProfile;


    public function __construct($resource, $userId = null)
    {
        parent::__construct($resource);
        $this->userId = $userId;
    }

    public function toArray($request): array
    {

        $this->profileId = getCurrentProfile($this->userId, $request) ?? null;
        $this->isRestricted = request()->is_restricted ?? null;
        $this->isChildProfile = getCurrentProfileSession('is_child_profile');

        $data = $this->getContentData();

        // Convert resource objects to arrays if needed
        if ($data instanceof JsonResource) {
            $data = $data->toArray($request);
        }

        return [
            'id' => $this->id,
            'title' => $this->name,
            'file_url' => setBaseUrlWithFileName($this->file_url, 'image', 'banner'),
            'type' => $this->type,

            'data' => $data,
        ];
    }

    private function getContentData()
    {
        return match ($this->type) {
            'movie', 'tvshow' => $this->getEntertainmentData(),
            'livetv' => $this->getLiveTvData(),
            'video' => $this->getVideoData(),
            default => null,
        };
    }


    private function getEntertainmentData()
    {
        $query = Entertainment::with('plan')
            ->where('id', $this->type_id)
            ->whereNull('deleted_at')
            ->where('status', 1);

        $this->applyRestrictions($query);

        $entertainment = $query->first();
        if (!$entertainment) {
            return null;
        }

        if ($entertainment->type === 'tvshow') {
            $hasSeason = Season::where('entertainment_id', $entertainment->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (!$hasSeason) {
                return null;
            }

            $hasEpisode = Episode::where('entertainment_id', $entertainment->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->exists();

            if (!$hasEpisode) {
                return null;
            }
        }
        $entertainment->is_watch_list = $this->getWatchlistStatus($entertainment->id);
        $entertainment->user_id = $this->userId;

        $entertainment = new CommonContentResourceV3($entertainment);
        return $entertainment->toArray(request());
    }


    private function getLiveTvData()
    {
        $livetv = LiveTvChannel::with('TvCategory')
            ->whereNull('deleted_at')
            ->where('status', 1)
            ->find($this->type_id);

        if (!$livetv) {
            return null;
        }

        $livetvResource = new LiveTvChannelResource($livetv);
        return $livetvResource->toArray(request());
    }


    private function getVideoData()
    {
        $query = Video::where('id', $this->type_id)
            ->whereNull('deleted_at')
            ->where('status', 1);

        $this->applyRestrictions($query);

        $video = $query->first();
        if (!$video) {
            return null;
        }

        $video->user_id = $this->userId;
        // $videoResource = new VideoResourceV3($video);
        $videoResource = new \Modules\Video\Transformers\VideoResourceBannerV3($video);
        return $videoResource;
    }


    private function applyRestrictions($query)
    {
        if ($this->isRestricted !== null) {
            $query->where('is_restricted', $this->isRestricted);
        }

        if (!empty($this->isChildProfile) && $this->isChildProfile != 0) {
            $query->where('is_restricted', 0);
        }
    }


    private function getWatchlistStatus($entertainmentId)
    {
        if (!$this->userId || !$this->profileId) {
            return false;
        }
        return Watchlist::where('entertainment_id', $entertainmentId)
            ->where('user_id', $this->userId)
            ->where('profile_id', $this->profileId)
            ->exists();
    }
}
