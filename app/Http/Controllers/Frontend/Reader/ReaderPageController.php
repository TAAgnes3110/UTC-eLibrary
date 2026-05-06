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
use App\Models\NewsPost;
use App\Models\Period;
use App\Services\BookService;
use App\Services\LoanPoliciesService;
use App\Services\NewsPostService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/** Trang công khai cho độc giả (không yêu cầu đăng nhập). */
class ReaderPageController extends Controller
{
    private const PUBLIC_NEWS_CACHE_TTL_SECONDS = 60;

    public function __construct(
        private BookService $bookService,
        private LoanPoliciesService $loanPoliciesService,
        private NewsPostService $newsPostService
    ) {}

    /**
     * @return array<string,mixed>
     */
    private function mapReaderNewsPost(NewsPost $post): array
    {
        $plainText = trim((string) preg_replace('/\s+/u', ' ', (string) strip_tags((string) $post->content)));

        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'content' => $post->content,
            'excerpt' => mb_substr($plainText, 0, 240),
            'type' => $post->type,
            'thumbnail_url' => $post->thumbnail_path ? Storage::url($post->thumbnail_path) : null,
            'published_at' => $post->published_at?->toIso8601String(),
            'posted_by' => $post->createdBy ? [
                'name' => $post->createdBy->name,
            ] : null,
        ];
    }

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
        $cacheVersion = (int) Cache::get(NewsPostService::READER_NEWS_CACHE_VERSION_KEY, 1);

        $latestNews = Cache::remember(
            sprintf('reader:home:latest-news:v%d', $cacheVersion),
            self::PUBLIC_NEWS_CACHE_TTL_SECONDS,
            fn (): array => array_values(array_map(
                fn (NewsPost $post): array => $this->mapReaderNewsPost($post),
                $this->newsPostService->paginatePublicPublished(['type' => NewsPost::TYPE_NEWS], 5, false)->items()
            ))
        );

        $latestNotices = Cache::remember(
            sprintf('reader:home:latest-notices:v%d', $cacheVersion),
            self::PUBLIC_NEWS_CACHE_TTL_SECONDS,
            fn (): array => array_values(array_map(
                fn (NewsPost $post): array => $this->mapReaderNewsPost($post),
                $this->newsPostService->paginatePublicPublished(['type' => NewsPost::TYPE_NOTICE], 5, false)->items()
            ))
        );

        $latestBooks = Cache::remember(
            sprintf('reader:home:latest-books:v%d', $cacheVersion),
            self::PUBLIC_NEWS_CACHE_TTL_SECONDS,
            fn (): array => ReaderBookCardResource::collection(
                Book::query()
                    ->with(['authors:id,name'])
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
            )->resolve()
        );

        return Inertia::render('Reader/Home', [
            'latestNews' => $latestNews,
            'latestNotices' => $latestNotices,
            'latestBooks' => $latestBooks,
        ]);
    }

    public function newsIndex(Request $request): Response
    {
        $request->validate([
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', 'string', 'in:news,notice'],
            'sort' => ['sometimes', 'nullable', 'string', 'in:newest,oldest'],
            'search_in' => ['sometimes', 'nullable', 'array'],
            'search_in.*' => ['string', 'in:title,content'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:30'],
        ]);

        $perPage = min(max((int) $request->input('per_page', 12), 1), 30);
        $keyword = trim((string) $request->input('keyword', ''));

        $news = $this->newsPostService->paginatePublicPublished([
            'keyword' => $keyword !== '' ? $keyword : null,
            'type' => $request->input('type'),
            'sort' => $request->input('sort', 'newest'),
            'search_in' => $request->input('search_in', []),
        ], $perPage, false);

        return Inertia::render('Reader/News/Index', [
            'news' => $news->through(fn (NewsPost $post): array => $this->mapReaderNewsPost($post)),
            'filters' => [
                'keyword' => $keyword,
                'type' => $request->input('type', ''),
                'sort' => $request->input('sort', 'newest'),
                'search_in' => $request->input('search_in', []),
                'per_page' => $perPage,
            ],
        ]);
    }

    public function newsShow(string $slug): Response
    {
        $post = $this->newsPostService->findPublishedBySlug($slug);
        abort_if(! $post instanceof NewsPost, 404);
        $cacheVersion = (int) Cache::get(NewsPostService::READER_NEWS_CACHE_VERSION_KEY, 1);

        $relatedNews = Cache::remember(
            sprintf('reader:news:related:%s:%d:v%d', (string) $post->type, (int) $post->id, $cacheVersion),
            self::PUBLIC_NEWS_CACHE_TTL_SECONDS,
            function () use ($post): array {
                $items = NewsPost::query()
                    ->select([
                        'id',
                        'slug',
                        'title',
                        'content',
                        'thumbnail_path',
                        'type',
                        'status',
                        'published_at',
                        'created_by',
                    ])
                    ->where('status', NewsPost::STATUS_ACTIVE)
                    ->where('type', $post->type)
                    ->whereKeyNot($post->id)
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get();

                return array_values(array_map(
                    fn (NewsPost $item): array => $this->mapReaderNewsPost($item),
                    $items->all()
                ));
            }
        );

        return Inertia::render('Reader/News/Show', [
            'post' => $this->mapReaderNewsPost($post),
            'relatedNews' => $relatedNews,
        ]);
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

        $resourceType = $request->input('resource_type') ?: null;
        $classificationId = $request->filled('classification_id') ? (int) $request->input('classification_id') : null;
        $stock = $request->input('stock') ?: null;
        $sort = $request->input('sort') ?: 'newest';

        return Inertia::render('Reader/Catalog', [
            'books' => fn () => $this->bookService->readerCatalog(
                $keyword !== '' ? $keyword : null,
                $resourceType,
                $perPage,
                $searchColumns,
                $classificationId,
                $stock,
                $sort,
            )->through(fn (Book $book) => (new ReaderBookCardResource($book))->resolve()),
            'filters' => [
                'keyword' => $keyword,
                'resource_type' => $resourceType,
                'classification_id' => $classificationId,
                'stock' => $stock,
                'per_page' => $perPage,
                'sort' => $sort,
                'search_in' => $request->input('search_in'),
            ],
            'classifications' => fn () => Classification::query()
                ->where('parent_id', null)
                ->orderBy('code', 'asc')
                ->get(['id', 'code', 'name']),
            'resourceTypeOptions' => fn () => array_merge(
                [['value' => '', 'label' => 'Tất cả loại sách']],
                array_map(
                    static fn (ResourceType $e) => [
                        'value' => $e->value,
                        'label' => ReaderBookCardResource::resourceTypeLabel($e->value),
                    ],
                    ResourceType::cases()
                )
            ),
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
            'faculties' => fn () => Faculty::query()
                ->orderBy('code', 'asc')
                ->get(['id', 'code', 'name']),
            'periods' => fn () => Period::query()
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
