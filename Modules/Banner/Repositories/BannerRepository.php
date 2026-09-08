<?php

namespace Modules\Banner\Repositories;

use Modules\Banner\Models\Banner;
use Illuminate\Support\Facades\Auth;

class BannerRepository implements BannerRepositoryInterface
{
    public function all()
    {
        return Banner::where('status', 1)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function find(int $id)
    {
        return Banner::withTrashed()
            ->whereNull('deleted_at')
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return Banner::create($data);
    }

    public function update(int $id, array $data)
    {
        $banner = Banner::findOrFail($id);
        $banner->update($data);
        return $banner;
    }

    public function delete(int $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->delete();
        return $banner;
    }

    public function restore(int $id)
    {
        $banner = Banner::withTrashed()->findOrFail($id);
        $banner->restore();
        return $banner;
    }

    public function forceDelete(int $id)    
    {
        $banner = Banner::withTrashed()->findOrFail($id);
        $banner->forceDelete();
        return $banner;
    }

    public function query()
    {
        $query = Banner::query()->withTrashed();

        if (Auth::user()->hasRole('user')) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    public function list(int $perPage, string $searchTerm = null)
    {
        $query = Banner::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
            ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }
}
