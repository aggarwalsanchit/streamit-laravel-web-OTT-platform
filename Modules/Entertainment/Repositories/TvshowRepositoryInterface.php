<?php

namespace Modules\Entertainment\Repositories;

interface TvshowRepositoryInterface
{
   
    public function getConfiguration();
    public function getTvshowDetails(int $id);
    public function getCastCrewDetail(int $id);
    public function getCastCrew(int $id);
    public function getTvshowVideo(int $id);

    
    
}