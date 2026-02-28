<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Helpers\ImageUploadHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index');
    }

    public function trash(): JsonResponse
    {
        $items = User::onlyTrashed()->orderByDesc('deleted_at')->get(['id', 'name', 'email', 'code', 'deleted_at'])->map(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'code' => $u->code,
            'deleted_at' => $u->deleted_at?->toIso8601String(),
        ]);
        return response()->json(['data' => $items]);
    }

    public function restore(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response()->json(['status' => 'error'], 410);
        }
        $user->restore();
        return response()->json(['status' => 'success']);
    }

    public function forceDelete(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return response()->json(['status' => 'error'], 410);
        }
        $user->forceDelete();
        return response()->json(['status' => 'success']);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error'], 404);
        }
        $user->is_active = !$user->is_active;
        $user->save();
        return response()->json(['status' => 'success', 'is_active' => $user->is_active]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error'], 404);
        }
        $user->delete();
        return response()->json(['status' => 'success']);
    }

    /**
     * Cập nhật ảnh đại diện cho 1 user. Admin gửi 1 file ảnh, hệ thống tự đặt tên và lưu.
     */
    public function updateAvatar(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy người dùng.'], 404);
        }
        $file = $request->file('avatar');
        if (!$file || !$file->isValid()) {
            return response()->json(['status' => 'error', 'message' => 'Vui lòng chọn một file ảnh hợp lệ.'], 422);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if (!in_array($ext, ImageUploadHelper::ALLOWED_EXTENSIONS, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chỉ chấp nhận ảnh: ' . implode(', ', ImageUploadHelper::ALLOWED_EXTENSIONS) . '.',
            ], 422);
        }
        try {
            ImageUploadHelper::deleteIfExists($user->avatar);
            $path = ImageUploadHelper::storeImage($file, 'avatars', (string) $user->id);
            $user->avatar = $path;
            $user->save();
            return response()->json(['status' => 'success', 'avatar' => $path]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}
