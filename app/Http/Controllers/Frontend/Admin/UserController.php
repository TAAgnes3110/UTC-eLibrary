<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
}
