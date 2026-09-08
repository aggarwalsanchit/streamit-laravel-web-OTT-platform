<?php

namespace Modules\Season\Repositories;

interface SeasonRepositoryInterface
{
    public function all();
    public function find(int $id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete(int $id);
    public function restore(int $id);
    public function forceDelete(int $id);
    public function list(array $perPage, string $searchTerm = null);    
    public function query();
}
