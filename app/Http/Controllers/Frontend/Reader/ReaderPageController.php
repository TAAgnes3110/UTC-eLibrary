<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Enums\LibraryCardStatus;
use App\Enums\ResourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryCardResource;
use App\Http\Resources\LoanPolicyResource;
use App\Http\Resources\ReaderBookCardResource;
use App\Http\Resources\ReaderBookDetailResource;
use App\Models\Book;
use App\Models\Classification;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\LoanPolicy;
use App\Models\Period;
use App\Services\BookService;
use App\Services\LoanPoliciesService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** Trang công khai cho độc giả (không yêu cầu đăng nhập). */
class ReaderPageController extends Controller
{
    public function __construct(
        private BookService $bookService,
        private LoanPoliciesService $loanPoliciesService
    ) {}

    /**
     * @return array{allow_home: bool, allow_onsite: bool, holder_type: string}|null
     */
    private function readerBorrowPermissions(?Authenticatable $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $card = LibraryCard::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('workflow_status', LibraryCard::WORKFLOW_ACTIVE)
            ->where('status', LibraryCardStatus::ACTIVE)
            ->first();

        if ($card === null) {
            return null;
        }

        $p = $this->loanPoliciesService->getBorrowPermissionsForHolderType((string) $card->holder_type);

        return [
            'allow_home' => $p['allow_home'],
            'allow_onsite' => $p['allow_onsite'],
            'holder_type' => (string) $card->holder_type,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loanPoliciesForReader(): array
    {
        $items = LoanPolicy::query()->orderedForReader()->get();

        return LoanPolicyResource::collection($items)->resolve();
    }

    public function home(): Response
    {
        return Inertia::render('Reader/Home');
    }

    public function about(): Response
    {
        return Inertia::render('Reader/About');
    }

    public function regulationsIndex(): Response
    {
        return Inertia::render('Reader/Regulations/Index');
    }

    public function regulationsCardProcedure(): Response
    {
        return Inertia::render('Reader/Regulations/CardProcedure');
    }

    public function regulationsSchedule(): Response
    {
        return Inertia::render('Reader/Regulations/Schedule');
    }

    public function regulationsBorrowing(): Response
    {
        return Inertia::render('Reader/Regulations/Borrowing', [
            'loanPolicies' => $this->loanPoliciesForReader(),
        ]);
    }

    public function catalog(Request $request): Response
    {
        $request->validate([
            'keyword' => ['sometimes', 'nullable', 'string', 'max:500'],
            'resource_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'classification_id' => ['sometimes', 'nullable', 'integer', 'exists:classifications,id'],
            'stock' => ['sometimes', 'nullable', 'string', 'in:in_stock,out_of_stock'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:60'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:newest,oldest'],
            'search_in' => ['sometimes', 'nullable', 'string'],
        ]);

        $perPage = min(60, max(1, (int) $request->input('per_page', 12)));
        $keyword = $request->input('keyword');
        $keyword = is_string($keyword) ? trim($keyword) : '';
        $searchColumns = $this->parseReaderSearchIn($request);

        $books = $this->bookService->readerCatalog(
            $keyword !== '' ? $keyword : null,
            $request->input('resource_type') ?: null,
            $perPage,
            $searchColumns,
            $request->filled('classification_id') ? (int) $request->input('classification_id') : null,
            $request->input('stock') ?: null,
            $request->input('sort') ?: 'newest',
        );

        $resourceTypeOptions = array_merge(
            [['value' => '', 'label' => 'Tất cả loại sách']],
            array_map(
                static fn (ResourceType $e) => [
                    'value' => $e->value,
                    'label' => ReaderBookCardResource::resourceTypeLabel($e->value),
                ],
                ResourceType::cases()
            )
        );

        return Inertia::render('Reader/Catalog', [
            'books' => $books->through(fn (Book $book) => (new ReaderBookCardResource($book))->resolve()),
            'filters' => [
                'keyword' => $keyword,
                'resource_type' => $request->input('resource_type'),
                'classification_id' => $request->input('classification_id'),
                'stock' => $request->input('stock'),
                'per_page' => $perPage,
                'sort' => $request->input('sort') ?: 'newest',
                'search_in' => $request->input('search_in'),
            ],
            'classifications' => Classification::query()->roots()->orderBy('code')->get(['id', 'code', 'name']),
            'resourceTypeOptions' => $resourceTypeOptions,
        ]);
    }

    public function catalogShow(Book $book): Response
    {
        $book = $this->bookService->getForApiDetail($book);
        $user = request()->user();
        $hasActiveLibraryCard = false;
        if ($user !== null) {
            $hasActiveLibraryCard = LibraryCard::query()
                ->where('user_id', $user->id)
                ->where('workflow_status', LibraryCard::WORKFLOW_ACTIVE)
                ->where('status', LibraryCardStatus::ACTIVE)
                ->exists();
        }

        return Inertia::render('Reader/BookShow', [
            'book' => (new ReaderBookDetailResource($book))->resolve(),
            'availability' => $this->bookService->readerCopyStats($book),
            'has_active_library_card' => $hasActiveLibraryCard,
            'borrow_permissions' => $this->readerBorrowPermissions($user),
        ]);
    }

    /**
     * @return list<string>|null
     */
    private function parseReaderSearchIn(Request $request): ?array
    {
        if (! $request->filled('search_in')) {
            return null;
        }
        $raw = $request->input('search_in');
        $candidates = is_array($raw)
            ? $raw
            : array_map('trim', explode(',', (string) $raw));
        $allowed = ['code', 'title', 'author', 'publisher', 'place', 'year', 'classification'];
        $filtered = array_values(array_intersect($candidates, $allowed));

        return $filtered === [] ? null : $filtered;
    }

    public function services(): Response
    {
        return Inertia::render('Reader/Services/Index');
    }

    public function servicesLibraryCard(Request $request): Response
    {
        $user = $request->user();
        if ($user === null) {
            return Inertia::render('Reader/Services/LibraryCard', [
                'auth_required' => true,
                'card' => null,
                'profile' => null,
                'faculties' => [],
                'periods' => [],
            ]);
        }

        $card = LibraryCard::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->with(['faculty:id,code,name', 'period:id,code,name'])
            ->first();

        $avatar = $user->avatar;
        if (! empty($avatar) && ! str_starts_with((string) $avatar, 'http')) {
            $avatar = Storage::disk('public')->exists((string) $avatar)
                ? Storage::url((string) $avatar)
                : null;
        }

        return Inertia::render('Reader/Services/LibraryCard', [
            'auth_required' => false,
            'card' => $card ? (new LibraryCardResource($card))->resolve() : null,
            'profile' => [
                'code' => $user->code,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'avatar' => $avatar,
                'faculty_id' => $user->faculty_id,
                'period_id' => $user->period_id,
                'class_code' => $user->class_code,
                'user_type' => $user->user_type instanceof \BackedEnum ? $user->user_type->value : $user->user_type,
            ],
            'faculties' => Faculty::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
            'periods' => Period::query()
                ->orderByDesc('start_year')
                ->get(['id', 'code', 'name', 'start_year', 'end_year']),
        ]);
    }

    public function servicesLoanRequests(): Response
    {
        return Inertia::render('Reader/Loans/Index');
    }

    public function servicesDigitalDocuments(): Response
    {
        return Inertia::render('Reader/Services/DigitalDocuments');
    }

    public function servicesBorrowCart(Request $request): Response
    {
        return Inertia::render('Reader/Services/BorrowCart', [
            'borrow_permissions' => $this->readerBorrowPermissions($request->user()),
        ]);
    }

    public function servicesLoanRequestShow(int $loan): Response
    {
        return Inertia::render('Reader/Loans/Show', ['loanId' => $loan]);
    }

    /** Trang tài khoản độc giả (cập nhật thông tin — layout reader, không dùng /admin). */
    public function profile(): Response
    {
        return Inertia::render('Reader/Profile');
    }

    public function profileUpdateRequests(): Response
    {
        return Inertia::render('Reader/ProfileUpdateRequests');
    }

    public function changePassword(): Response
    {
        return Inertia::render('Reader/ChangePassword');
    }
}
