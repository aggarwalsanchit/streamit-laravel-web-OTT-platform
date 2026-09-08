<?php

namespace Modules\Episode\Repositories;

interface EpisodeRepositoryInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);    
    public function update(int $id, array $data);
    public function delete(int $id);
    public function restore(int $id);
    public function forceDelete(int $id);
    public function list(array $perPage, string $searchTerm = null);
    public function query();
    public function saveQualityMappings(int $id, array $videoQuality, array $qualityVideoUrl, array $videoQualityType,array $qualityVideoFile);
    public function storeDownloads( array $data,int $id);


}
