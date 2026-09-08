<?php

namespace Modules\Genres\Services;

use Modules\Genres\Repositories\GenreRepositoryInterface;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GenreService
{
    protected $genreRepository;

    public function __construct(GenreRepositoryInterface $genreRepository)
    {
        $this->genreRepository = $genreRepository;
    }

    public function getAllGenres()
    {
        return $this->genreRepository->all();
    }

    public function getGenreById(int $id)
    {
        return $this->genreRepository->find($id);
    }

    /**
     * Clear all genre-related cache keys
     * Since cache keys follow pattern: 'genres_' . $page . '_per_' . $perPage . '_search_' . md5($searchTerm)
     * We clear cache for common page/per_page combinations
     */
    protected function clearGenreCache()
    {
        // Clear common cache key patterns
        $commonPerPages = [10, 14, 20, 50, 100];
        $maxPages = 10; // Clear first 10 pages
        
        for ($page = 1; $page <= $maxPages; $page++) {
            foreach ($commonPerPages as $perPage) {
                // Clear cache for different search terms (empty and common patterns)
                $searchTerms = [null, ''];
                foreach ($searchTerms as $searchTerm) {
                    $cacheKey = 'genres_' . $page . '_per_' . $perPage . '_search_' . md5($searchTerm);
                    Cache::forget($cacheKey);
                }
            }
        }
        
        // Clear the generic cache key
        Cache::forget('genres_');
        
        // Clear the 'genres' cache key used by API endpoints
        Cache::forget('genres');
        Cache::forget('genres_v2');
        
        // Clear MobileSetting cache to ensure fresh genre IDs are retrieved
        Cache::forget('setting');
    }

    public function createGenre(array $data)
    {
        $this->clearGenreCache();

        $data['slug'] = Str::slug($data['name']);
       
        return $this->genreRepository->create($data);
    }

    public function updateGenre(int $id, array $data)
    {
        // Clear banner-data API cache FIRST (before updating) to ensure immediate effect
        // This must happen BEFORE the update so cache is cleared with old data
      
        $this->clearGenreCache();
       
        $result = $this->genreRepository->update($id, $data);
        
        // Clear cache again after update to ensure fresh data
        clearBannerDataCache();
        
        return $result;
    }

    public function deleteGenre(int $id)
    {
        $this->clearGenreCache();
        // Clear banner-data API cache when genre is soft deleted
        return $this->genreRepository->delete($id);
    }

    public function restoreGenre($id)
    {
        $this->clearGenreCache();
        $result = $this->genreRepository->restore($id);
        return $result;
    }

    public function forceDeleteGenre($id)
    {
        $this->clearGenreCache();
        // Clear banner-data API cache when genre is permanently deleted
        return $this->genreRepository->forceDelete($id);
    }

    public function getDataTable(Datatables $datatable, array $filter)
    {
        $query = $this->getFilteredData($filter);
        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" data-type="genres" onclick="dataTableRowCheck(' . $row->id . ',this)">';
            })
            ->editColumn('image', function ($data) {

                $imageUrl = setBaseUrlWithFileName($data->file_url, 'image', 'genres');
                return view('components.image-name', ['image' => $imageUrl, 'name' => $data->name])->render();
            })
            ->addColumn('action', function ($data) {
                return view('genres::backend.genres.action', compact('data'));
            })
            ->editColumn('status', function ($row) {
                $checked = $row->status ? 'checked="checked"' : '';
                $disabled = $row->trashed() ? 'disabled' : '';
                return '
                    <div class="form-check form-switch">
                        <input type="checkbox" data-url="' . route('backend.genres.update_status', $row->id) . '"
                            data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                            id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>
                ';
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);
                return $diff < 25 ? $data->updated_at->diffForHumans() : $data->updated_at->isoFormat('llll');
            })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'status', 'check', 'image'])
            ->toJson();
    }

    public function getFilteredData(array $filter)
    {
        $query = $this->genreRepository->query();

        if (isset($filter['column_status'])) {
            $query->where('status', $filter['column_status']);
        }

        if (isset($filter['name'])) {
            $query->where('name', 'like', '%' . $filter['name'] . '%');
        }

        return $query;
    }

    public function getGenresList(int $perPage, string $searchTerm = null)
    {
        return $this->genreRepository->list($perPage, $searchTerm);
    }
}
