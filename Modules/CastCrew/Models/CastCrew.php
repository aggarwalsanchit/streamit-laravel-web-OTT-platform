<?php

namespace Modules\CastCrew\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use  Modules\Entertainment\Models\EntertainmentTalentMapping;

class CastCrew extends BaseModel
{
    use SoftDeletes;

     protected $table = 'cast_crew';

     protected $fillable = ['name', 'type','file_url','tmdb_id','bio','place_of_birth','dob','designation','status'];


     public function entertainmentTalentMappings()
     {
         return $this->hasMany(EntertainmentTalentMapping::class,'talent_id','id');
     }


     protected static function boot()
     {
         parent::boot();

         // Clear dashboard cache when cast/crew status is updated
         static::updated(function ($castcrew) {
             // Clear dashboard cache when status changes (affects Popular Personalities section)
             if ($castcrew->isDirty('status')) {
                 if (function_exists('clearDashboardCache')) {
                     clearDashboardCache();
                 }
                 if (function_exists('clearSearchV3Cache')) {
                     clearSearchV3Cache();
                 }
             }
         });

         static::deleting(function ($castcrew) {
             // Clear dashboard cache when cast/crew is deleted
             if (function_exists('clearDashboardCache')) {
                 clearDashboardCache();
             }

             if ($castcrew->isForceDeleting()) {

                 $castcrew->entertainmentTalentMappings()->forcedelete();

             } else {
                 $castcrew->entertainmentTalentMappings()->delete();
              }

         });

         static::restoring(function ($castcrew) {
             // Clear dashboard cache when cast/crew is restored
             if (function_exists('clearDashboardCache')) {
                 clearDashboardCache();
             }

             $castcrew->entertainmentTalentMappings()->withTrashed()->restore();

         });
     }

     /**
     * Fetch cast/crew by ids and format for frontend cards
     *
     * @param array<int> $castIds
     * @return array<int, array<string, mixed>>
     */
    public static function getFrontendCardsByIds(array $castIds): array
    {
        $casts = self::whereIn('id', $castIds)->where('deleted_at', null)->get(['id', 'name', 'type', 'file_url']);

        return $casts->map(function (self $value): array {
            return [
                'id' => $value->id,
                'name' => $value->name,
                'type' => $value->type,
                'profile_image' => setBaseUrlWithFileName($value->file_url, 'image', 'castcrew'),
            ];
        })->all();
    }


}
