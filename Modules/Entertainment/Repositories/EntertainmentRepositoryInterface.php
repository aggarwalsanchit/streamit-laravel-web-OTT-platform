<?php

namespace Modules\Entertainment\Repositories;

interface EntertainmentRepositoryInterface
{
    public function all();
    public function movieGenres(int $id);
    public function moviecountries(int $id);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function restore(int $id);
    public function forceDelete(int $id);
    public function list(array $filters);
    public function query();
    public function saveGenreMappings(array $data, int $id);
    public function saveCountryMappings(array $data, int $id);
    public function saveTalentMappings(array $data, int $id);
    public function saveQualityMappings(int $id, array $videoQuality, array $qualityVideoUrl, array $videoQualityType, array $qualityVideoFile);
    public function storeDownloads( array $data, int $id);

}
