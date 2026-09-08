<?php

namespace Modules\CastCrew\Services;

use Modules\CastCrew\Repositories\CastCrewRepositoryInterface;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CastCrewService
{
    protected $castcrewRepository;

    public function __construct( CastCrewRepositoryInterface $castcrewRepository)
    {
        $this->castcrewRepository = $castcrewRepository;
    }

    public function getAll()
    {
        return $this->castcrewRepository->all();
    }

    public function getById(int $id)
    {
        return $this->castcrewRepository->find($id);
    }

    public function create(array $data)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        return $this->castcrewRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        
        // Clear search_v3 cache when actor/director is updated (especially status changes)
        clearSearchV3Cache();
        
        // Clear dashboard cache when actor/director status is updated (affects Popular Personalities section)
        if (function_exists('clearDashboardCache')) {
            clearDashboardCache();
        }
        
        return $this->castcrewRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        $result = $this->castcrewRepository->delete($id);
        return $result;
    }

    public function restore(int $id)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        $result = $this->castcrewRepository->restore($id);
        return $result;    
    }

    public function forceDelete(int $id)
    {
        $cacheKey = 'castcrew_list';
        Cache::forget($cacheKey);
        $result = $this->castcrewRepository->forceDelete($id);
        return $result;
    }

    public function getDataTable(Datatables $datatable, array $filter, string $type, Request $request = null)
    {
        $query = $this->getFilteredData($filter ,$type);
        
        return $datatable->eloquent($query)
        ->addColumn('check', function ($row) {
            return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="cast-crew" onclick="dataTableRowCheck('.$row->id.', this)">';
        })
        ->editColumn('image', function ($data) {
            $designation = $data->designation;
            $type = 'castcrew';
            $imageUrl = setBaseUrlWithFileName($data->file_url,'image','castcrew');
            return view('components.media-item', ['thumbnail' => $imageUrl, 'name' => $data->name, 'designation' => $designation, 'type' => $type])->render();
        })

        ->editColumn('dob', function ($data) {

            $dob = $data->dob ? formatDate($data->dob) : '-';

           return  $dob ;
        })
        ->editColumn('status', function ($row) {
            $checked = $row->status ? 'checked="checked"' : '';
            $disabled = $row->trashed() ? 'disabled' : '';
            return '
                <div class="form-check form-switch">
                    <input type="checkbox" data-url="' . route('backend.castcrew.update_status', $row->id) . '"
                        data-token="' . csrf_token() . '" class="switch-status-change form-check-input"
                        id="datatable-row-' . $row->id . '" name="status" value="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                </div>
            ';
        })
        ->editColumn('place_of_birth', function ($data) {

            $place_of_birth = $data->place_of_birth ?  $data->place_of_birth : '-';

           return  $place_of_birth ;
        })
        ->addColumn('designation', function ($data) {
            return $data->designation ?? '-';
        })

        ->filterColumn('dob', function($query, $keyword) {
            if (!empty($keyword)) {
                $this->applyDobSearch($query, $keyword);
            }
        })
        ->filterColumn('designation', function($query, $keyword) {
            if (!empty($keyword)) {
                $query->where('designation', 'like', '%' . $keyword . '%');
            }
        })
        ->orderColumn('image', function ($query, $order) {
            $query->orderBy('name', $order);
        })

        ->addColumn('action', function ($data) {
            return view('castcrew::backend.castcrew.action', compact('data'));
        })



        ->editColumn('updated_at', function ($data) {


            $diff = Carbon::now()->diffInHours($data->updated_at);

            if ($diff < 25) {
                return $data->updated_at->diffForHumans();
            } else {
                return $data->updated_at->isoFormat('llll');
            }
         })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['action', 'check','image', 'status'])
            ->toJson();
    }

    public function getFilteredData(array $filter, string $type)
    {
        $query = $this->castcrewRepository->query();

        if($type!=null){

            $query = $query->where('type',$type);
        }

        if(isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }

        if (isset($filter['type'])) {
            $query->where('type', $filter['type']);
        }

        if (isset($filter['name'])) {
            $query->where('name', 'like', '%' . $filter['name'] . '%');
        }

        // Don't apply default ordering here - let DataTables handle all sorting
        // The initial order is set in the frontend (orderColumn: [[7, "desc"]])

        return $query;
    }

    public function getGenresList(int $perPage, string $searchTerm = null)
    {
        return $this->castcrewRepository->list($perPage, $searchTerm);
    }

    
    private function applyDobSearch(Builder $query, string $keyword)
    {
        $keyword = trim(strtolower($keyword));
        if (empty($keyword)) return;
    
        // --- YEAR ONLY (e.g. "1982") ---
        if (preg_match('/^\d{4}$/', $keyword)) {
            $query->whereYear('dob', (int)$keyword);
            return;
        }
    
        // --- NUMERIC SEARCH (e.g. "9", "19", "29") ---
        if (is_numeric($keyword)) {
            $num = (int)$keyword;
    
            $query->where(function ($q) use ($num, $keyword) {
                // Match by day
                if ($num >= 1 && $num <= 31) {
                    $q->whereDay('dob', $num)
                        ->orWhereRaw("CAST(DAY(dob) AS CHAR) LIKE ?", ["%$keyword%"]);
                }
    
                // Match by month
                if ($num >= 1 && $num <= 12) {
                    $q->orWhereMonth('dob', $num);
                }
    
                // Match year part
                $q->orWhereRaw("CAST(YEAR(dob) AS CHAR) LIKE ?", ["%$keyword%"]);
            });
            return;
        }
    
        // --- WEEKDAY NAMES (e.g. "Tue", "Tuesday") ---
        $weekdayMap = [
            'sun' => 1, 'sunday' => 1,
            'mon' => 2, 'monday' => 2,
            'tue' => 3, 'tuesday' => 3,
            'wed' => 4, 'wednesday' => 4,
            'thu' => 5, 'thursday' => 5,
            'fri' => 6, 'friday' => 6,
            'sat' => 7, 'saturday' => 7,
        ];
    
        foreach ($weekdayMap as $dayName => $dayNum) {
            if (str_contains($dayName, $keyword) || str_contains($keyword, $dayName)) {
                $query->whereRaw("DAYOFWEEK(dob) = ?", [$dayNum]);
                return;
            }
        }
    
        // --- MONTH NAMES (e.g. "Nov", "December") ---
        $monthMap = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];
    
        foreach ($monthMap as $monthName => $monthNum) {
            if (str_contains($monthName, $keyword) || str_contains($keyword, $monthName)) {
                $query->whereMonth('dob', $monthNum);
                return;
            }
        }
    
        // --- FULL DATE FORMATS (merged + extended) ---
        $dateFormats = [
            'Y-m-d', 'm-d-Y', 'd-m-Y', 'd/m/Y', 'm/d/Y',
            'Y/m/d', 'Y.m.d', 'd.m.Y', 'm.d.Y',
            'jS M Y', 'M jS Y', 'D, M d, Y', 'D, d M, Y',
            'D, M jS Y', 'D, jS M Y', 'F j, Y', 'd F, Y',
            'jS F, Y', 'l jS F Y', 'l, F j, Y',
            'D, M d Y', 'D, d M Y', 'F jS, Y', 'j F Y', // Extra fallbacks
        ];
    
        $cleanKeyword = ucwords(strtolower(trim(preg_replace('/\s+/', ' ', $keyword))));

        foreach ($dateFormats as $fmt) {
            try {
                $parsed = \Carbon\Carbon::createFromFormat($fmt, $cleanKeyword);
                if ($parsed) {
                    $query->whereDate('dob', $parsed->format('Y-m-d'));
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
    
        // --- FLEXIBLE PARSING (last fallback) ---
        try {
            $parsed = \Carbon\Carbon::parse($keyword);
            if ($parsed && $parsed->isValid()) {
                $query->whereDate('dob', $parsed->format('Y-m-d'));
            }
        } catch (\Exception $e) {
            // Ignore if still invalid
        }
    }
  
  

}
