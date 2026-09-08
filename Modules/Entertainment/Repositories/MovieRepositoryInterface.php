<?php

namespace Modules\Entertainment\Repositories;

interface MovieRepositoryInterface
{
   
    public function getConfiguration();
    public function getMovieDetails(int $id);
    public function getCastCrewDetail(int $id);
    public function getCastCrew(int $id);
    public function getMovieVideo(int $id);

    
    
}