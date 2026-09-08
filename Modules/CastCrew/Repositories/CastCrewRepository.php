<?php

namespace Modules\CastCrew\Repositories;

use Modules\CastCrew\Models\CastCrew;
use Auth;
use Illuminate\Support\Facades\Cache;

class CastCrewRepository implements CastCrewRepositoryInterface
{
    public function all()
    {
        return CastCrew::all();
    }

    public function find(int $id)   
    {
        $genreQuery = CastCrew::query();

        if (Auth::user()->hasRole('user')) {
            $genreQuery->whereNull('deleted_at'); // Only show non-trashed genres
        }

        $genre = $genreQuery->withTrashed()->findOrFail($id);
        $genre->file_url = setBaseUrlWithFileName($genre->file_url,'image','castcrew');
        return $genre;
    }

    public function create(array $data)
    {
        return CastCrew::create($data);
        Cache::forget('dashboard_detail_data_v3');
        Cache::forget('search_v3');
    }

    public function update(int $id, array $data)
    {
        $genre = CastCrew::findOrFail($id);
        $genre->update($data);
        Cache::forget('dashboard_detail_data_v3');
        Cache::forget('search_v3');
        return $genre;
    }

    public function delete(int $id)
    {
        $genre = CastCrew::findOrFail($id);
        Cache::forget('dashboard_detail_data_v3');
        Cache::forget('search_v3');
        $genre->delete();
        return $genre;
    }

    public function restore(int $id)
    {
        $genre = CastCrew::withTrashed()->findOrFail($id);
        $genre->restore();
        Cache::forget('dashboard_detail_data_v3');
        Cache::forget('search_v3');
        return $genre;
    }

    public function forceDelete(int $id)
    {
        $genre = CastCrew::withTrashed()->findOrFail($id);
        $genre->forceDelete();
        Cache::forget('dashboard_detail_data_v3');
        Cache::forget('search_v3');
        return $genre;
    }

    public function query()
    {

        $genreQuery=CastCrew::query()->withTrashed();

        if(Auth::user()->hasRole('user') ) {
            $genreQuery->whereNull('deleted_at');
        }

        return $genreQuery;

    }

    public function list(int $perPage, string $searchTerm = null)
    {
        $query = CastCrew::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }


}
