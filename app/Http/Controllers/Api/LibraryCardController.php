<?php

namespace App\Http\Controllers\Api;

use App\Enums\LibraryCardStatus;
use App\Enums\UploadDirectory;
use App\Exports\LibraryCardExport;
use App\Helpers\ApiResponse;
use App\Helpers\LoanHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryCardRequest;
use App\Http\Resources\LibraryCardResource;
use App\Models\LibraryCard;
use App\Services\LibraryCard\LibraryCardService;
use App\Services\LoanPoliciesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CRUD + trash / restore / force + trạng thái hoạt động (thủ thư/admin).
 */
class LibraryCardController extends Controller
{
    private const MAX_BULK_IDS = 200;

    private const MAX_EXPORT_IDS = 500;

    public function __construct(
        private LibraryCardService $libraryCardService,
        private LoanPoliciesService $loanPoliciesService,
        private LoanHelper $loanHelper
    ) {}

    public function index(Request $request): JsonResponse
    {
        $keyword = $request->input('keyword');
        $perPage = max(1, min(100, (int) $request->input('per_page', LibraryCardService::PER_PAGE)));
        $workflowStatuses = $this->parseWorkflowStatusFilter($request);
        $holderType = $this->parseHolderTypeFilter($request);
        $cardStatus = $this->parseCardStatusFilter($request);
        $keywordColumns = $this->parseSearchInFilter($request);
        $managementListOnly = $request->boolean('management');
        $sortBy = $this->parseSortByFilter($request);
        $items = $this->libraryCardService->index(
            $keyword,
            $perPage,
            $workflowStatuses,
            $holderType,
            $cardStatus,
            $keywordColumns,
            $managementListOnly,
            $sortBy,
        );

        return ApiResponse::success(LibraryCardResource::collection($items));
    }

    /**
     * Tra cứu thẻ theo mã in trên thẻ khi tạo phiếu mượn (thủ thư/admin).
     * Trả về quyền mượn + hạn mức (đọc qua cache — thường Redis nếu CACHE_STORE=redis).
     */
    public function lookupForLoan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_number' => ['required', 'string', 'max:64'],
        ]);

        $result = $this->libraryCardService->resolveForLoanByCardNumber((string) $validated['card_number']);

        if ($result['status'] === 'not_found') {
            return ApiResponse::error('Không có thẻ với mã này trong hệ thống.', 404);
        }

        if ($result['status'] === 'not_eligible') {
            return ApiResponse::error('Thẻ chưa ở trạng thái được phép mượn (chưa kích hoạt hoặc đã ngưng).', 422);
        }

        if ($result['status'] === 'locked') {
            return ApiResponse::error('Thẻ đang bị khóa, không thể mượn sách.', 422);
        }

        $card = $result['card'];
        $holderType = (string) $card->holder_type;
        $permissions = $this->loanPoliciesService->getBorrowPermissionsForHolderType($holderType);
        $limits = $this->loanPoliciesService->getBorrowLimitsForHolderType($holderType);
        $currentBorrowed = $this->loanHelper->currentOutstandingBorrowCounts($card);

        return ApiResponse::success([
            'card' => new LibraryCardResource($card),
            'allow_home' => $permissions['allow_home'],
            'allow_onsite' => $permissions['allow_onsite'],
            'limits' => $limits,
            'current_borrowed' => $currentBorrowed,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $rawIds = $request->input('ids');
        if (! is_array($rawIds) && $rawIds !== null && $rawIds !== '' && is_numeric($rawIds)) {
            $request->merge(['ids' => [(int) $rawIds]]);
        }

        $validated = $request->validate([
            'ids' => ['sometimes', 'nullable', 'array', 'max:'.self::MAX_EXPORT_IDS],
            'ids.*' => ['integer'],
        ]);
        $ids = $validated['ids'] ?? null;
        $ids = is_array($ids) ? array_values(array_filter($ids, static fn ($v) => is_numeric($v))) : null;

        return LibraryCardExport::stream($ids);
    }

    /**
     * @return list<string>|null
     */
    private function parseWorkflowStatusFilter(Request $request): ?array
    {
        if (! $request->filled('workflow_status')) {
            return null;
        }
        $raw = $request->input('workflow_status');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $allowed = [
            LibraryCard::WORKFLOW_DRAFT,
            LibraryCard::WORKFLOW_PENDING_PAYMENT,
            LibraryCard::WORKFLOW_PENDING_REVIEW,
            LibraryCard::WORKFLOW_PENDING_PICKUP,
            LibraryCard::WORKFLOW_ACTIVE,
            LibraryCard::WORKFLOW_REJECTED,
            LibraryCard::WORKFLOW_CANCELLED,
            LibraryCard::WORKFLOW_EXPIRED,
            LibraryCard::WORKFLOW_REVOKED,
        ];
        $filtered = array_values(array_intersect($candidates, $allowed));

        return $filtered === [] ? null : $filtered;
    }

    private function parseHolderTypeFilter(Request $request): ?string
    {
        if (! $request->filled('holder_type')) {
            return null;
        }
        $v = (string) $request->input('holder_type');
        $allowed = [
            LibraryCard::HOLDER_TYPE_STUDENT,
            LibraryCard::HOLDER_TYPE_TEACHER,
            LibraryCard::HOLDER_TYPE_EXTERNAL,
        ];

        return in_array($v, $allowed, true) ? $v : null;
    }

    private function parseCardStatusFilter(Request $request): ?int
    {
        if (! $request->filled('status')) {
            return null;
        }
        $n = (int) $request->input('status');
        $allowed = LibraryCardStatus::values();

        return in_array($n, $allowed, true) ? $n : null;
    }

    /**
     * @return list<string>|null
     */
    private function parseSearchInFilter(Request $request): ?array
    {
        if (! $request->filled('search_in')) {
            return null;
        }
        $raw = $request->input('search_in');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $allowed = ['card_number', 'code', 'full_name', 'email', 'phone'];
        $filtered = array_values(array_intersect($candidates, $allowed));

        return $filtered === [] ? null : $filtered;
    }

    private function parseSortByFilter(Request $request): ?string
    {
        if (! $request->filled('sort_by')) {
            return null;
        }
        $v = (string) $request->input('sort_by');
        $allowed = ['newest', 'oldest', 'name_asc', 'name_desc'];

        return in_array($v, $allowed, true) ? $v : null;
    }

    public function show(LibraryCard $library_card): JsonResponse
    {
        $library_card->loadMissing([
            'payment',
            'period',
            'faculty',
            'department',
            'user',
        ]);

        return ApiResponse::success(new LibraryCardResource($library_card));
    }

    public function store(LibraryCardRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('photo')) {
            $dir = trim(UploadDirectory::forTable('library_cards'), '/');
            $data['photo_path'] = $request->file('photo')->store($dir, 'public');
        }
        unset($data['photo']);
        $card = $this->libraryCardService->create($data);
        $card->loadMissing(['payment.collector', 'period', 'faculty', 'department', 'user']);

        return ApiResponse::success(
            new LibraryCardResource($card),
            __('messages.success_create'),
            201
        );
    }

    public function updatePhoto(Request $request, LibraryCard $library_card): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|max:10240',
        ]);
        $file = $request->file('photo');
        if (! $file) {
            return ApiResponse::error(__('Vui lòng chọn một file ảnh hợp lệ.'), 422);
        }
        try {
            $card = $this->libraryCardService->updatePhoto($library_card, $file);
            $card->loadMissing(['payment', 'period', 'faculty', 'department', 'user']);

            return ApiResponse::success(new LibraryCardResource($card), __('messages.success_update'));
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function update(LibraryCardRequest $request, LibraryCard $library_card): JsonResponse
    {
        $card = $this->libraryCardService->updateLibraryCard($library_card, $request->validated());
        $card->loadMissing(['payment', 'period', 'faculty', 'department', 'user']);

        return ApiResponse::success(new LibraryCardResource($card), __('messages.success_update'));
    }

    public function destroy(LibraryCard $library_card): JsonResponse
    {
        $this->libraryCardService->destroy($library_card);

        return ApiResponse::success(null, __('messages.success_delete'));
    }

    public function trash(Request $request): JsonResponse
    {
        $perPage = max(1, min(100, (int) $request->input('per_page', LibraryCardService::PER_PAGE)));
        $items = $this->libraryCardService->trash($perPage);

        return ApiResponse::success(LibraryCardResource::collection($items));
    }

    public function restore(int $id): JsonResponse
    {
        $card = $this->libraryCardService->restore($id);
        if ($card === null) {
            return ApiResponse::notFound();
        }
        $card->loadMissing(['payment.collector', 'period', 'faculty', 'department', 'user']);

        return ApiResponse::success(new LibraryCardResource($card), __('messages.success_restore'));
    }

    public function restoreMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|max:'.self::MAX_BULK_IDS,
            'ids.*' => 'integer',
        ]);
        $restored = $this->libraryCardService->restoreMany($request->input('ids', []));

        return ApiResponse::success(['restored' => $restored], __('messages.success_restore'));
    }

    public function forceDelete(int $id): JsonResponse
    {
        if (! $this->libraryCardService->forceDeleteTrashed($id)) {
            return ApiResponse::notFound(__('messages.error_404'));
        }

        return ApiResponse::success(null, __('messages.success_force_delete'));
    }

    public function forceDeleteMany(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|max:'.self::MAX_BULK_IDS,
            'ids.*' => 'integer',
        ]);
        $deleted = $this->libraryCardService->forceDeleteManyTrashed($request->input('ids', []));

        return ApiResponse::success(['deleted' => $deleted], __('messages.success_force_delete'));
    }
}
