<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
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
     * @param Request $request
     * @param $id
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
     * @param $id
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
        } else {
            return $this->jsonResponse([
                'status' => 'error',
                'messages' => __('messages.error_410'),
                'data' => [],
            ], 410);
        }
    }

    /**
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
     * @param User $user
     * @param array $data
     * @return JsonResponse
     * @todo Trả về phản hồi khi thông tin đã tồn tại
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
