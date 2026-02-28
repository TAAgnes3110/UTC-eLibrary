<?php

namespace App\Http\Controllers\Backend;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller quản lý người dùng (CRUD, thùng rác, cập nhật trạng thái).
 * Đối với user chỉ có xuất Excel (export), không có nhập Excel (import).
 *
 * @todo Thêm filter theo user_type (ADMIN, LIBRARIAN, MEMBER) trong index.
 * @todo Thêm export danh sách người dùng ra Excel/CSV (chỉ xuất, không nhập).
 */
class UserController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * Danh sách người dùng có phân trang, tìm theo từ khóa (tên, mã, email, SĐT, số thẻ).
     *
     * @param Request $request Query: keyword (optional).
     * @return JsonResponse Paginated list với relation libraryCard.
     */
    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');

        $items = User::query()
            ->when($keyword, fn($query) => $query->where(function ($q) use ($keyword) {
                $q->where('id', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhereHas('libraryCard', fn($sub) => $sub->where('card_number', 'like', "%{$keyword}%"));
            }))
            ->with(['libraryCard', 'faculty:id,code,name', 'department:id,code,name,faculty_id'])
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return ApiResponse::success($items);
    }

    /**
     * Thêm người dùng mới (có thể kèm thẻ thư viện).
     *
     * @param UserRequest $request Validated: code, name, email, phone, password, user_type, card_number?, is_active?.
     * @return JsonResponse 201 + data user + libraryCard, hoặc 400 nếu trùng email/code/phone.
     */
    public function store(UserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $cardNumber = $data['card_number'] ?? null;
        unset($data['card_number']);

        if ($existingUser = User::duplicate($data)->first()) {
            return $this->existingUserResponse($existingUser, $data);
        }

        $user = User::create($data);

        if ($cardNumber) {
            $user->libraryCard()->create([
                'card_number' => $cardNumber,
                'status' => 'active',
                'is_active' => true,
                'issue_date' => now(),
            ]);
        }

        return ApiResponse::success($user->load('libraryCard'), __('messages.success_create'), 201);
    }

    /**
     * Cập nhật thông tin người dùng (có thể cập nhật thẻ thư viện).
     *
     * @param UserRequest $request Validated: code, name, email, phone, password?, user_type, card_number?, is_active?.
     * @param int $id ID người dùng.
     * @return JsonResponse 200 + data user + libraryCard, 410 nếu không tìm thấy, 400 nếu trùng thông tin.
     */
    public function update(UserRequest $request, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }

        $data = $request->validated();
        $cardNumber = $data['card_number'] ?? null;
        unset($data['card_number']);
        if (empty($data['password'])) {
            unset($data['password']);
        }

        if ($existingUser = User::duplicate($data, $id)->first()) {
            return $this->existingUserResponse($existingUser, $data);
        }

        $user->update($data);

        if ($cardNumber !== null) {
            $user->libraryCard()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'card_number' => $cardNumber,
                    'status' => 'active',
                    'is_active' => true,
                    'issue_date' => now(),
                ]
            );
        }

        return ApiResponse::success($user->load('libraryCard'), __('messages.success_update'));
    }

    /**
     * Cập nhật trạng thái hoạt động (is_active) hàng loạt.
     *
     * @param Request $request Body: ids (array int), is_active (bool).
     * @return JsonResponse 200 success.
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'is_active' => 'required|boolean',
        ]);

        User::query()
            ->whereIn('id', $request->ids)
            ->update(['is_active' => $request->boolean('is_active')]);

        return ApiResponse::success(null, __('messages.success_update'));
    }

    /**
     * Xóa mềm người dùng.
     *
     * @param int $id ID người dùng.
     * @return JsonResponse 200 success, 410 nếu không tìm thấy.
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        $user->delete();
        return ApiResponse::success(null, __('messages.success_delete'));
    }

    /**
     * Danh sách người dùng đã xóa mềm (thùng rác).
     *
     * @param Request $request (hiện không dùng query).
     * @return JsonResponse { data: [{ id, name, email, code, deleted_at }] }.
     */
    public function trash(Request $request): JsonResponse
    {
        $items = User::onlyTrashed()
            ->orderByDesc('deleted_at')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'code' => $u->code,
                'deleted_at' => $u->deleted_at?->toIso8601String(),
            ]);

        return ApiResponse::success($items->toArray());
    }

    /**
     * Khôi phục người dùng từ thùng rác.
     *
     * @param int $id ID người dùng đã xóa mềm.
     * @return JsonResponse 200 success, 410 nếu không tìm thấy.
     */
    public function restore(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        $user->restore();
        return ApiResponse::success(null, __('Đã khôi phục.'));
    }

    /**
     * Xóa vĩnh viễn người dùng (chỉ với bản ghi đã xóa mềm).
     *
     * @param int $id ID người dùng trong thùng rác.
     * @return JsonResponse 200 success, 410 nếu không tìm thấy.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return ApiResponse::notFound();
        }
        $user->forceDelete();
        return ApiResponse::success(null, __('Đã xóa vĩnh viễn.'));
    }

    /**
     * Trả về lỗi khi thông tin (email/mã/SĐT) đã tồn tại.
     *
     * @param User $user User trùng (để so sánh field).
     * @param array $data Dữ liệu gửi lên (email, code, phone).
     * @return JsonResponse
     */
    private function existingUserResponse(User $user, array $data): JsonResponse
    {
        $message = __('Thông tin đã được sử dụng.');
        if (isset($data['email']) && $user->email === $data['email']) {
            $message = __('Email đã tồn tại trong hệ thống.');
        } elseif (isset($data['code']) && $user->code === $data['code']) {
            $message = __('Mã số (MSV/CCCD) đã tồn tại trong hệ thống.');
        } elseif (!empty($data['phone']) && $user->phone === $data['phone']) {
            $message = __('Số điện thoại đã tồn tại trong hệ thống.');
        }
        return ApiResponse::error($message, 400);
    }
}
