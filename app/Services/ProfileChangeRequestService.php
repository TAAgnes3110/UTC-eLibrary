<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Faculty;
use App\Models\LibrarySetting;
use App\Models\ProfileChangeRequest;
use App\Models\User;

class ProfileChangeRequestService
{
    public function pageData(User $user): array
    {
        $user->load(['faculty:id,code,name', 'department:id,name,faculty_id']);

        $faculties = Faculty::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $departments = Department::where('is_active', true)->orderBy('faculty_id')->orderBy('name')->get(['id', 'name', 'faculty_id']);
        $cohorts = LibrarySetting::get('cohorts_list', ['K60', 'K61', 'K62', 'K63', 'K64', 'K65', 'K66']);
        $myRequests = ProfileChangeRequest::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'code' => $user->code,
                'cohort' => $user->cohort,
                'faculty_id' => $user->faculty_id,
                'faculty' => $user->faculty?->name,
                'department_id' => $user->department_id,
                'department' => $user->department?->name,
            ],
            'faculties' => $faculties,
            'departments' => $departments,
            'cohorts' => $cohorts ?? [],
            'myRequests' => $myRequests,
        ];
    }

    /**
     * @return array{success: true}|array{success: false, message: string, code: int, errors?: array}
     */
    public function store(User $user, string $field, string $valueNew, ?string $proofPath): array
    {
        $valueNew = trim($valueNew);

        if ($field === 'faculty_id') {
            if (!Faculty::where('id', (int) $valueNew)->exists()) {
                return ['success' => false, 'message' => 'Khoa không hợp lệ.', 'code' => 422, 'errors' => ['value_new' => ['Khoa không hợp lệ.']]];
            }
            $valueOld = (string) $user->faculty_id;
        } elseif ($field === 'department_id') {
            $dept = Department::where('is_active', true)->find((int) $valueNew);
            if (!$dept) {
                return ['success' => false, 'message' => 'Lớp không hợp lệ.', 'code' => 422, 'errors' => ['value_new' => ['Lớp không hợp lệ.']]];
            }
            $valueOld = $user->department_id ? (string) $user->department_id : '';
        } elseif ($field === 'cohort') {
            $cohorts = LibrarySetting::get('cohorts_list', []);
            if (!in_array($valueNew, $cohorts, true)) {
                return ['success' => false, 'message' => 'Khóa không hợp lệ.', 'code' => 422, 'errors' => ['value_new' => ['Khóa không hợp lệ.']]];
            }
            $valueOld = $user->cohort ?? '';
        } else {
            $valueOld = $user->code ?? '';
        }

        if ($valueOld === $valueNew) {
            return ['success' => false, 'message' => 'Giá trị mới trùng với hiện tại.', 'code' => 422];
        }

        ProfileChangeRequest::create([
            'user_id' => $user->id,
            'field' => $field,
            'value_old' => $valueOld,
            'value_new' => $valueNew,
            'proof_path' => $proofPath,
            'status' => 'pending',
        ]);

        return ['success' => true];
    }

    public function index(string $status): array
    {
        $query = ProfileChangeRequest::with(['user:id,name,email,cohort,faculty_id', 'user.faculty:id,name', 'reviewer:id,name'])
            ->orderByDesc('created_at');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $items = $query->paginate(20)->withQueryString();
        $faculties = Faculty::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $departments = Department::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return [
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
            'faculties' => $faculties,
            'departments' => $departments,
            'statusFilter' => $status,
        ];
    }

    public function approve(int $id, int $reviewerId): bool
    {
        $req = ProfileChangeRequest::where('status', 'pending')->find($id);
        if (!$req) {
            return false;
        }
        $user = User::findOrFail($req->user_id);

        if ($req->field === 'cohort') {
            $user->update(['cohort' => $req->value_new]);
        } elseif ($req->field === 'faculty_id') {
            $user->update(['faculty_id' => (int) $req->value_new ?: null, 'department_id' => null]);
        } elseif ($req->field === 'department_id') {
            $user->update(['department_id' => (int) $req->value_new ?: null]);
        } elseif ($req->field === 'code') {
            $user->update(['code' => $req->value_new]);
        }

        $req->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return true;
    }

    public function reject(int $id, int $reviewerId, ?string $adminNote): bool
    {
        $req = ProfileChangeRequest::where('status', 'pending')->find($id);
        if (!$req) {
            return false;
        }
        $req->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'admin_note' => $adminNote,
        ]);
        return true;
    }
}
