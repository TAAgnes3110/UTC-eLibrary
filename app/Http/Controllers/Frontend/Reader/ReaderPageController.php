<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Enums\ResourceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryCardResource;
use App\Http\Resources\LoanPolicyResource;
use App\Http\Resources\ReaderBookCardResource;
use App\Http\Resources\ReaderBookDetailResource;
use App\Models\Book;
use App\Models\Classification;
use App\Models\ClassificationDetail;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\LoanPolicy;
use App\Models\Period;
use App\Services\BookService;
use App\Services\SavedBookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** Trang công khai cho độc giả (không yêu cầu đăng nhập). */
class ReaderPageController extends Controller
{
    public function __construct(
        private BookService $bookService,
        private SavedBookService $savedBookService
    ) {}

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
            'classification_detail_id' => ['sometimes', 'nullable', 'integer', 'exists:classification_details,id'],
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
            $request->filled('classification_detail_id') ? (int) $request->input('classification_detail_id') : null,
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
                'classification_detail_id' => $request->input('classification_detail_id'),
                'stock' => $request->input('stock'),
                'per_page' => $perPage,
                'sort' => $request->input('sort') ?: 'newest',
                'search_in' => $request->input('search_in'),
            ],
            'classifications' => Classification::query()->orderBy('code')->get(['id', 'code', 'name']),
            'classificationDetails' => ClassificationDetail::query()->orderBy('code')->get(['id', 'classification_id', 'code', 'name']),
            'resourceTypeOptions' => $resourceTypeOptions,
        ]);
    }

    public function catalogShow(Book $book): Response
    {
        $book = $this->bookService->getForApiDetail($book);
        $user = auth()->user();
        $isSaved = $user !== null && $this->savedBookService->isSaved($user, (int) $book->id);

        return Inertia::render('Reader/BookShow', [
            'book' => (new ReaderBookDetailResource($book))->resolve(),
            'availability' => $this->bookService->readerCopyStats($book),
            'is_saved' => $isSaved,
        ]);
    }

    public function savedBooks(Request $request): Response
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:60'],
        ]);
        $perPage = min(60, max(1, (int) $request->input('per_page', 12)));
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $paginator = $this->savedBookService->paginateForUser($user, $perPage);

        return Inertia::render('Reader/Services/SavedBooks', [
            'saved' => $paginator->through(function ($saved) {
                return [
                    'id' => $saved->id,
                    'saved_at' => $saved->created_at?->toIso8601String(),
                    'book' => (new ReaderBookCardResource($saved->book))->resolve(),
                ];
            }),
            'filters' => [
                'per_page' => $perPage,
            ],
        ]);
    }

    public function storeSavedBook(Request $request, Book $book): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $this->savedBookService->save($user, $book);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back(status: 303);
    }

    public function destroySavedBook(Request $request, Book $book): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(403);
        }
        $this->savedBookService->unsave($user, $book);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'success']);
        }

        return back(status: 303);
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
