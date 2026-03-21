<?php

namespace App\Services;

use App\Enums\RoleType;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\FacultyResource;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Department;
use App\Models\Faculty;
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
            $classificationDetails = ClassificationDetail::orderBy('classification_id')->orderBy('code')->get(['id', 'code', 'name', 'classification_id']);
            $taxonomy = app(TaxonomyCacheService::class);
            return [
                'faculties' => FacultyResource::collection($faculties)->resolve(),
                'departments' => DepartmentResource::collection($departments)->resolve(),
                'cohorts' => $taxonomy->getCohorts(),
                'role_types' => RoleType::getRoleTypes(),
                'classifications' => $classifications->map(fn ($c) => [
                    'id' => $c->id,
                    'code' => $c->code,
                    'name' => $c->name,
                ])->all(),
                'classification_details' => $classificationDetails->map(fn ($d) => [
                    'id' => $d->id,
                    'classification_id' => $d->classification_id,
                    'code' => $d->code,
                    'name' => $d->name,
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
