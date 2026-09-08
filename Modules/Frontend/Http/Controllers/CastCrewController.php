<?php

namespace Modules\Frontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\CastCrew\Models\CastCrew;
use Modules\CastCrew\Transformers\CastCrewListResource;
use Modules\Entertainment\Models\Entertainment;
use App\Models\UserSearchHistory;
use \Modules\Entertainment\Models\Review;
use Carbon\Carbon;

class CastCrewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('frontend::index');
    }


    public function castcrewList()
    {
        return view('frontend::castCrew');
    }
    public function castCrewDetail(Request $request, int $id)
    {
        $castcrew = CastCrew::where('id',$id)->first();
        if(!$castcrew){
            return redirect()->route('user.login');
        }
        $responseData = New CastCrewListResource($castcrew);

        $data=$responseData->toArray(request());


        $more_items =Entertainment::with([
            'entertainmentTalentMappings' => function ($query) use ($id) {
                $query->where('talent_id', $id);
            }
        ])->limit(12)->get();

        // Movie count - matching API filter logic
        $movieCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
            $query->where('talent_id', $id);
        })
        ->where('type', 'movie')
        ->where('status', 1)
        ->whereNull('deleted_at')
        ->where(function ($query) {
            $query->whereDate('release_date', '<=', Carbon::now())
                  ->orWhereNull('release_date');
        })
        ->count();

        // TV show count - matching API filter logic (including seasons with episodes check)
        $tvshowCount = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
            $query->where('talent_id', $id);
        })
        ->where('type', 'tvshow')
        ->where('status', 1)
        ->whereNull('deleted_at')
        ->where(function ($query) {
            $query->whereDate('release_date', '<=', Carbon::now())
                  ->orWhereNull('release_date');
        })
        ->whereHas('season', function ($seasonQuery) {
            $seasonQuery->where('status', 1)
                ->whereNull('deleted_at')
                ->whereHas('episodes', function ($episodeQuery) {
                    $episodeQuery->where('status', 1)
                        ->whereNull('deleted_at');
                });
        })
        ->count();


        // Average rating - filter by active entertainments only
        $averageRating = Review::whereHas('entertainment', function ($query) use ($id) {
            $query->whereHas('entertainmentTalentMappings', function ($subQuery) use ($id) {
                $subQuery->where('talent_id', $id);
            })
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where(function ($dateQuery) {
                $dateQuery->whereDate('release_date', '<=', Carbon::now())
                          ->orWhereNull('release_date');
            });
        })->avg('rating');


        // Top genres - matching API filter logic
        $topGenres = Entertainment::whereHas('entertainmentTalentMappings', function ($query) use ($id) {
            $query->where('talent_id', $id);
        })
        ->where('status', 1)
        ->whereNull('deleted_at')
        ->where(function ($query) {
            $query->whereDate('release_date', '<=', Carbon::now())
                  ->orWhereNull('release_date');
        })
        ->with(['entertainmentGenerMappings.genre:id,name'])
        ->get()
        ->pluck('entertainmentGenerMappings')
        ->flatten()
        ->pluck('genre.name')
        ->filter()
        ->countBy()
        ->sortDesc()
        ->take(1)
        ->keys()
        ->implode(', ');


        if($request->has('is_search') && $request->is_search==1){

            $user_id=auth()->user()->id ?? $request->user_id;

                  if($user_id){

                     $currentprofile=GetCurrentprofile($user_id, $request);

                     if($currentprofile){

                        $existingSearch = UserSearchHistory::where('user_id', $user_id)
                        ->where('profile_id', $currentprofile)
                        ->where('search_query', $data['name'])
                        ->first();

                     if (!$existingSearch) {

                         UserSearchHistory::create([
                            'user_id' => $user_id,
                            'profile_id' => $currentprofile,
                            'search_query' => $data['name'],
                            'search_id'=> $data['id'],
                            'type'=>'castcrew',
                           ]);
                         }

                       }

                   }

        }

        return view('frontend::castCrewDetail', compact('data','more_items','movieCount','tvshowCount','averageRating','topGenres'));
    }

    public function moviecastcrewList(string $type, $id)
    {

        $entertainment_id=$id;
        $type=$type;

        return view('frontend::castCrew', compact('entertainment_id','type'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('frontend::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        return view('frontend::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)   
    {
        return view('frontend::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): RedirectResponse
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
