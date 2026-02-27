<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Danh sách người dùng có phân trang, tìm theo từ khóa (tên, mã, email, SĐT, số thẻ).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');

        $items = User::query()
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('id', 'like', '%' . $keyword . '%')
                        ->orwhere('name', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhereHas('libraryCard', function ($query) use ($keyword) {
                            $query->where('card_number', 'like', "%{$keyword}%");
                        });
                });
            })
            ->with(['libraryCard'])
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return $this->jsonResponse($items);
    }

    /**
     * Thêm người dùng mới (có thể kèm thẻ thư viện).
     *
     * @param UserRequest $request
     * @return JsonResponse
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->except(['id']);
        if ($existingUser = User::duplicate($data)->first()) {
            return $this->existingUserResponse($existingUser, $data);
        }
        $card_number = $data['card_number'] ?? null;
        unset($data['card_number']);
        $user = User::create($data);
        if ($card_number) {
            $user->libraryCard()->create([
                'card_number' => $card_number,
                'status' => 'active',
                'is_active' => true,
                'issue_date' => now(),
            ]);
        }

        return $this->jsonResponse([
            'status' => 'success',
            'data' => $user->load('libraryCard'),
            'messages' => __('messages.success_create')
        ], 201);
    }

    /**
     * Cập nhật thông tin người dùng (có thể cập nhật thẻ thư viện).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $item = User::query()->find($id);
        if (!$item) {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('messages.error_410'),
                'data' => [],
            ], 410);
        }
        $data = $request->all();
        if ($existingUser = User::duplicate($data, $id)->first()) {
            return $this->existingUserResponse($existingUser, $data);
        }

        $card_number = $data['card_number'] ?? null;
        unset($data['card_number']);

        $item->fill($data);
        if ($item->save()) {
            if ($card_number) {
                $item->libraryCard()->updateOrCreate(
                    ['user_id' => $item->id],
                    [
                        'card_number' => $card_number,
                        'status' => 'active',
                        'is_active' => true,
                        'issue_date' => now(),
                    ]
                );
            }

            return $this->jsonResponse([
                'status' => 'success',
                'messages' => __('messages.success_update'),
                'data' => $item->load('libraryCard')
            ]);
        }

        return $this->jsonResponse([
            'status' => 'error',
            'messages' => __('messages.error_update'),
        ]);
    }

    /**
     * Cập nhật trạng thái hàng loạt cho danh sách user (theo ids và status).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        if ($request->has('ids') && $request->has('status') && $request->ids) {
            User::query()
                ->whereIn('id', $request->ids)
                ->update(['status' => $request->status]);
        }
        return $this->jsonResponse([
            'status' => 'success',
            'messages' => __('messages.success_update')
        ]);
    }

    /**
     * Xóa người dùng theo id.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $item = User::query()->find($id);
        if ($item) {
            $item->delete();
            return $this->jsonResponse([
                'status' => 'success',
                'messages' => __('messages.success_delete')
            ]);
        }
        return $this->jsonResponse([
            'status' => 'error',
            'messages' => __('messages.error_410'),
            'data' => [],
        ], 410);
    }

    /**
     * Danh sách người dùng đã xóa mềm (thùng rác).
     */
    public function trash(Request $request): JsonResponse
    {
        $items = User::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'code' => $u->code,
                'deleted_at' => $u->deleted_at?->toIso8601String(),
            ]);
        return $this->jsonResponse(['data' => $items]);
    }

    /**
     * Khôi phục người dùng từ thùng rác.
     */
    public function restore($id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return $this->jsonResponse(['status' => 'error', 'messages' => __('messages.error_410')], 410);
        }
        $user->restore();
        return $this->jsonResponse(['status' => 'success', 'messages' => __('Đã khôi phục.')]);
    }

    /**
     * Xóa vĩnh viễn người dùng.
     */
    public function forceDelete($id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return $this->jsonResponse(['status' => 'error', 'messages' => __('messages.error_410')], 410);
        }
        $user->forceDelete();
        return $this->jsonResponse(['status' => 'success', 'messages' => __('Đã xóa vĩnh viễn.')]);
    }

    /**
     * Trả về phản hồi khi thông tin người dùng (email/mã/SĐT) đã tồn tại trong hệ thống.
     *
     * @param User $user
     * @param array $data
     * @return JsonResponse
     */
    public function existingUserResponse(User $user, array $data): JsonResponse
    {
        $message = __('Thông tin đã được sử dụng.');
        if (isset($data['email']) && $user->email === $data['email']) {
            $message = __('Email đã tồn tại trong hệ thống.');
        } elseif (isset($data['code']) && $user->code === $data['code']) {
            $message = __('Mã số (MSV/CCCD) đã tồn tại trong hệ thống.');
        } elseif (!empty($data['phone']) && $user->phone === $data['phone']) {
            $message = __('Số điện thoại đã tồn tại trong hệ thống.');
        }

        return $this->jsonResponse([
            'status' => 'error',
            'messages' => $message
        ], 400);
    }
}
