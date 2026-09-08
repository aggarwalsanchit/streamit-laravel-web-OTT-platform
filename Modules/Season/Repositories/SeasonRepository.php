<?php

namespace Modules\Season\Repositories;

use Modules\Season\Models\Season;
use Auth;

class SeasonRepository implements SeasonRepositoryInterface
{
    public function all()
    {
        $query = Season::query();

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc')->get();

        return $query;
    }

    public function find(int $id)
    {
        $season = Season::query();

        if (Auth::user()->hasRole('user')) {
            $season->whereNull('deleted_at'); // Only show non-trashed genres
        }

        $season = $season->withTrashed()->findOrFail($id);

        return $season;
    }

    public function create(array $data)
    {

        return Season::create($data);
    }

    public function update($id, array $data)
    {
        $season = Season::findOrFail($id);
        $season->update($data);
        return $season;
    }

    public function delete(int $id)
    {
        $season = Season::findOrFail($id);
        $season->delete();
        return $season;
    }

    public function restore(int $id)
    {
        $season = Season::withTrashed()->findOrFail($id);
        $season->restore();
        return $season;
    }

    public function forceDelete(int $id)    
    {
        $season = Season::withTrashed()->findOrFail($id);
        $season->forceDelete();
        return $season;
    }

    public function query()
    {

        $season=Season::query()->withTrashed();

        if(Auth::user()->hasRole('user') ) {
            $season->whereNull('deleted_at'); 
        }
    
        return $season;
       
    }

    public function list(array $perPage, string $searchTerm = null)
    {
        $query = Season::query();

        if ($searchTerm) {
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $query->where('status', 1)
              ->orderBy('updated_at', 'desc');

        return $query->paginate($perPage);
    }

    
}
