<?php

namespace Modules\Banner\Repositories;

interface BannerRepositoryInterface
{
    public function all();
    public function find(int $id);  
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function restore(int $id);
    public function forceDelete(int $id);
    public function list(int $perPage, string $searchTerm = null);
    public function query();


}


