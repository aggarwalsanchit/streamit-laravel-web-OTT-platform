<?php

namespace Modules\Banner\Services;

use Modules\Banner\Repositories\BannerRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class BannerService
{
    protected $bannerRepository;

    public function __construct( BannerRepositoryInterface $bannerRepository)
    {
        $this->bannerRepository = $bannerRepository;
    }

    public function getAll()
    {
        return $this->bannerRepository->all();
    }

    public function getById(int $id)
    {
        return $this->bannerRepository->find($id);
    }

    public function create(array $data, Request $request)
    {
        $cacheKey = 'banner_list';
        Cache::forget($cacheKey);

        $data['type_id'] = $request->input('type_id');
        $data['type_name'] = $request->input('type_name');

        $banner = $this->bannerRepository->create($data);

        return $banner;
    }

    public function update(int $id, array $data)
    {
        $cacheKey = 'banner_list';
        clearRelatedCache($cacheKey, 'banner');

        return $this->bannerRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $cacheKey = 'banner_list';
        clearRelatedCache($cacheKey, 'banner');

        return $this->bannerRepository->delete($id);
    }

    public function restore(int $id)
    {
        $cacheKey = 'banner_list';
        clearRelatedCache($cacheKey, 'banner');
        return $this->bannerRepository->restore($id);
    }

    public function forceDelete(int $id)
    {
        $cacheKey = 'banner_list';
        clearRelatedCache($cacheKey, 'banner');
        return $this->bannerRepository->forceDelete($id);
    }


}
