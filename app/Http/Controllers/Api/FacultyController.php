<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\FacultyRequest;
use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use App\Services\FacultyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacultyController extends Controller
{
    public function __construct(
        private FacultyService $facultyService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $result = $this->facultyService->list(
            $request->input('search'),
            $request->has('page'),
            (int) $request->input('per_page', 15)
        );
        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return ApiResponse::success([
                'data' => FacultyResource::collection($result->items()),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                    'from' => $result->firstItem(),
                    'to' => $result->lastItem(),
                ],
            ]);
        }
        return ApiResponse::success(FacultyResource::collection($result));
    }

    public function show(Faculty $faculty): JsonResponse
    {
        return ApiResponse::success(new FacultyResource($faculty));
    }

    public function store(FacultyRequest $request): JsonResponse
    {
        $faculty = $this->facultyService->create($request->validated());
        return ApiResponse::success(new FacultyResource($faculty), __('messages.success_create'), 201);
    }

    public function update(FacultyRequest $request, Faculty $faculty): JsonResponse
    {
        $faculty = $this->facultyService->update($faculty, $request->validated());
        return ApiResponse::success(new FacultyResource($faculty));
    }

    public function destroy(Faculty $faculty): JsonResponse
    {
        $this->facultyService->delete($faculty);
        return ApiResponse::success(null, null, 204);
    }
}
