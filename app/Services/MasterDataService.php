<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\FacultyResource;
use App\Models\Classification;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Period;
use Illuminate\Support\Facades\Cache;

class MasterDataService
{
    private const CACHE_KEY = 'api:master-data';

    private const CACHE_TTL = 3600;

    public function getPayload(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $faculties = Faculty::where('is_active', true)->orderBy('name')->get();
            $departments = Department::where('is_active', true)->orderBy('faculty_id')->orderBy('name')->get();
            $classifications = Classification::orderBy('code')->get(['id', 'code', 'name']);
            $taxonomy = app(TaxonomyCacheService::class);
            $periods = Period::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'code', 'name', 'start_year', 'end_year']);

            return [
                'faculties' => FacultyResource::collection($faculties)->resolve(),
                'departments' => DepartmentResource::collection($departments)->resolve(),
                'periods' => $periods->map(fn (Period $p) => [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'start_year' => $p->start_year,
                    'end_year' => $p->end_year,
                ])->all(),
                'cohorts' => $taxonomy->getCohorts(),
                'role_types' => RoleType::getRoleTypes(),
                'classifications' => $classifications->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                ])->all(),
            ];
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        MasterLookupCacheService::clear();
    }
}
