<?php

namespace App\Services;

use App\Models\Faculty;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class FacultyService
{
    public function list(?string $search = null, bool $paginate = false, int $perPage = 15): LengthAwarePaginator|Collection
    {
        $query = Faculty::query()->orderBy('name');
        if ($search !== null && $search !== '') {
            $query->where(function ($qb) use ($search) {
                $qb->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }
        if ($paginate) {
            return $query->paginate($perPage)->withQueryString();
        }
        return $query->get();
    }

    public function create(array $data): Faculty
    {
        $faculty = Faculty::create($data);
        MasterDataService::clearCache();
        return $faculty;
    }

    public function update(Faculty $faculty, array $data): Faculty
    {
        $faculty->update($data);
        MasterDataService::clearCache();
        return $faculty;
    }

    public function delete(Faculty $faculty): void
    {
        $faculty->delete();
        MasterDataService::clearCache();
    }
}
