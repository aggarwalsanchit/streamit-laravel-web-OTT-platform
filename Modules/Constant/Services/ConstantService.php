<?php

namespace Modules\Constant\Services;

use Modules\Constant\Repositories\ConstantRepositoryInterface;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Cache;

class ConstantService
{
    protected $ConstantRepository;

    public function __construct(ConstantRepositoryInterface $constantRepository)
    {
        $this->constantRepository = $constantRepository;
    }

    public function getAll()
    {
        return $this->constantRepository->all();
    }

    public function getById(int $id)
    {
        return $this->constantRepository->find($id);
    }


    public function create(array $data)
    {
        $cacheKey = 'constant_list';
        Cache::forget($cacheKey);
        $data['poster_url'] = setDefaultImage($data['poster_url']);
       $constant = $this->constantRepository->create($data);

        return $constant;
    }

    public function update(int $id, array $data)
    {
        $cacheKey = 'constant_list';
        Cache::forget($cacheKey);
        return $this->constantRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $cacheKey = 'constant_list';
        Cache::forget($cacheKey);
        return $this->constantRepository->delete($id);
    }

    public function restore(int $id)
    {
        $cacheKey = 'constant_list';
        Cache::forget($cacheKey);
        return $this->constantRepository->restore($id);
    }

    public function forceDelete(int $id)
    {
        $cacheKey = 'constant_list';
        Cache::forget($cacheKey);
        return $this->constantRepository->forceDelete($id);
    }

   

    public function getList(int $perPage, string $searchTerm = null)
    {
        return $this->constantRepository->list($perPage, $searchTerm);
    }

}
