<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Enums\DigitalAssetPreviewStatus;
use App\Enums\LibraryCardStatus;
use App\Enums\ResourceType;
use App\Helpers\FileHelpers;
use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalPurchaseCartItemResource;
use App\Http\Resources\LibraryCardResource;
use App\Http\Resources\LoanPolicyResource;
use App\Http\Resources\ReaderBookCardResource;
use App\Http\Resources\ReaderBookDetailResource;
use App\Models\Book;
use App\Models\Classification;
use App\Models\DigitalAsset;
use App\Models\Faculty;
use App\Models\LibraryCard;
use App\Models\LoanPolicy;
use App\Models\NewsPost;
use App\Models\Period;
use App\Services\BookService;
use App\Services\DigitalAssetPreviewDisplayService;
use App\Services\DigitalAssetPreviewService;
use App\Services\DigitalAssetService;
use App\Services\DigitalPaywallService;
use App\Services\DigitalPurchaseCartService;
use App\Services\LoanPoliciesService;
use App\Services\NewsPostService;
use App\Support\DigitalAssetPreviewJobDispatcher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
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
        private NewsPostService $newsPostService,
        private DigitalPaywallService $digitalPaywallService,
        private DigitalPurchaseCartService $digitalPurchaseCartService,
        private DigitalAssetService $digitalAssetService,
        private DigitalAssetPreviewService $digitalAssetPreviewService,
        private DigitalAssetPreviewDisplayService $digitalAssetPreviewDisplayService,
    ) {}

    /**
     * @return array<string,mixed>
     */
    private function mapReaderNewsPost(NewsPost $post): array
    {
        $plainText = trim((string) preg_replace('/\s+/u', ' ', (string) strip_tags((string) $post->content)));
        /** @var FilesystemAdapter $mediaStorage */
        $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));

        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'content' => FileHelpers::rewriteAbsoluteMediaUrlsInHtml((string) $post->content),
            'excerpt' => mb_substr($plainText, 0, 240),
            'type' => $post->type,
            'thumbnail_url' => $post->thumbnail_path
                ? $mediaStorage->url($post->thumbnail_path)
                : FileHelpers::mediaDefaultUrl('news_thumbnail'),
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
        return $this->readerLibraryCardContext($user)['permissions'];
    }

    /**
     * @return array{has_active_card: bool, permissions: array{allow_home: bool, allow_onsite: bool, holder_type: string}|null}
     */
    private function readerLibraryCardContext(?Authenticatable $user): array
    {
        if ($user === null) {
            return ['has_active_card' => false, 'permissions' => null];
        }

        $card = LibraryCard::query()
            ->where('user_id', $user->getAuthIdentifier())
            ->where('workflow_status', LibraryCard::WORKFLOW_ACTIVE)
            ->where('status', LibraryCardStatus::ACTIVE)
            ->first(['id', 'holder_type']);

        if ($card === null) {
            return ['has_active_card' => false, 'permissions' => null];
        }

        $p = $this->loanPoliciesService->getBorrowPermissionsForHolderType((string) $card->holder_type);

        return [
            'has_active_card' => true,
            'permissions' => [
                'allow_home' => $p['allow_home'],
                'allow_onsite' => $p['allow_onsite'],
                'holder_type' => (string) $card->holder_type,
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildReaderDigitalStats(Book $book, DigitalAsset $asset, ?Authenticatable $user): ?array
    {
        $aggregated = $this->digitalAssetService->aggregatedReaderStatsForBook($book);
        $userHasFull = false;
        $isOwnSubmission = false;
        if ($user !== null) {
            $userId = (int) $user->id;
            $isOwnSubmission = $this->digitalPaywallService->userIsApprovedSubmitterOfAsset($userId, $asset);
            $userHasFull = $this->digitalPaywallService->userCanDownloadPdf($userId, $asset);
        }

        return [
            'digital_asset_id' => (int) $asset->id,
            'access_sessions' => $aggregated['view_count'],
            'downloads' => $aggregated['download_count'],
            'user_can_download_pdf' => $userHasFull,
            'is_own_approved_submission' => $isOwnSubmission,
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

        $readerHasLibraryCardRecord = false;
        $user = request()->user();
        if ($user !== null) {
            $readerHasLibraryCardRecord = LibraryCard::query()
                ->where('user_id', $user->id)
                ->exists();
        }

        return Inertia::render('Reader/Home', [
            'latestNews' => $latestNews,
            'latestNotices' => $latestNotices,
            'latestBooks' => $latestBooks,
            'reader_has_library_card_record' => $readerHasLibraryCardRecord,
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

        $books = $this->bookService->readerCatalog(
            $keyword !== '' ? $keyword : null,
            $resourceType,
            $perPage,
            $searchColumns,
            $classificationId,
            $stock,
            $sort,
        );
        $books->through(fn (Book $book) => (new ReaderBookCardResource($book))->resolve());

        return Inertia::render('Reader/Catalog', [
            'books' => $books,
            'filters' => [
                'keyword' => $keyword,
                'resource_type' => $resourceType,
                'classification_id' => $classificationId,
                'stock' => $stock,
                'per_page' => $perPage,
                'sort' => $sort,
                'search_in' => $request->input('search_in'),
            ],
            'classifications' => Inertia::optional(fn () => Classification::query()
                ->orderBy('code', 'asc')
                ->get(['id', 'code', 'name'])),
            'resourceTypeOptions' => Inertia::optional(fn () => array_merge(
                [['value' => '', 'label' => 'Tất cả loại sách']],
                array_map(
                    static fn (ResourceType $e) => [
                        'value' => $e->value,
                        'label' => ReaderBookCardResource::resourceTypeLabel($e->value),
                    ],
                    ResourceType::cases()
                )
            )),
        ]);
    }

    public function catalogShow(Book $book): Response
    {
        $book = $this->bookService->getForReaderDetail($book);
        $user = request()->user();
        $isDigital = $book->resource_type === ResourceType::DIGITAL;
        $cardContext = $this->readerLibraryCardContext($user);

        $primaryAsset = $isDigital ? $this->digitalAssetService->resolvePrimaryAsset($book) : null;
        if ($isDigital && $primaryAsset) {
            $this->digitalAssetService->incrementViewCount($primaryAsset);
        } elseif (! $isDigital) {
            $this->bookService->incrementReaderCatalogView($book);
        }

        $digitalStats = ($isDigital && $primaryAsset)
            ? $this->buildReaderDigitalStats($book, $primaryAsset, $user)
            : null;

        $availability = $isDigital
            ? ['total' => 0, 'available' => 0, 'borrowed' => 0, 'reserved_pending' => 0]
            : $this->bookService->readerCopyStats($book);

        $readerViewCount = ! $isDigital ? (int) $book->fresh()->view_count : null;

        $relatedBooks = ReaderBookCardResource::collection(
            $this->bookService->readerRelatedBooks($book, 12)
        )->resolve();

        return Inertia::render('Reader/BookShow', [
            'book' => (new ReaderBookDetailResource($book))->resolve(),
            'availability' => $availability,
            'has_active_library_card' => $cardContext['has_active_card'],
            'borrow_permissions' => $cardContext['permissions'],
            'digital_stats' => $digitalStats,
            'reader_view_count' => $readerViewCount,
            'related_books' => $relatedBooks,
        ]);
    }

    /** Danh sách đầy đủ sách liên quan với một đầu mục (gợi ý theo phân loại, tác giả, loại tài liệu). */
    public function catalogRelatedBooks(Book $book, Request $request): Response
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:12', 'max:48'],
        ]);

        $book = $this->bookService->getForReaderDetail($book);
        $perPage = min(48, max(12, (int) $request->input('per_page', 12)));

        $paginator = $this->bookService->readerRelatedBooksPaginated($book, $perPage);
        $paginator->through(fn (Book $row) => (new ReaderBookCardResource($row))->resolve());

        return Inertia::render('Reader/RelatedBooks', [
            'source_book' => [
                'id' => (int) $book->id,
                'title' => (string) $book->title,
            ],
            'books' => $paginator,
        ]);
    }

    /** Trang xem trước — hiển thị PNG/text từ preview_display (không stream PDF, không tạo sync trên request). */
    public function catalogDigitalPreviewShow(Book $book, DigitalAsset $digital_asset): Response
    {
        if ((int) $digital_asset->book_id !== (int) $book->id) {
            abort(404);
        }

        $backUrl = route('reader.catalog.show', ['book' => $book->id], false);
        $state = $this->digitalAssetPreviewService->resolveReaderPreviewState($digital_asset);

        if ($state === DigitalAssetPreviewStatus::Disabled->value) {
            abort(404, __('Chưa có bản xem trước cho tài liệu này.'));
        }

        if ($state !== DigitalAssetPreviewStatus::Ready->value) {
            if (in_array($state, [
                DigitalAssetPreviewStatus::Pending->value,
                DigitalAssetPreviewStatus::Processing->value,
                DigitalAssetPreviewStatus::Failed->value,
            ], true) && config('deploy.run_post_upload_processing_on_host', true)) {
                DigitalAssetPreviewJobDispatcher::dispatch((int) $digital_asset->id);
            }

            return Inertia::render('Reader/BookDigitalPreview', [
                'book' => ['id' => (int) $book->id, 'title' => $book->title],
                'asset' => [
                    'id' => (int) $digital_asset->id,
                    'original_name' => $digital_asset->original_name,
                ],
                'pages' => [],
                'back_url' => $backUrl,
                'preview_state' => $state,
                'preview_message' => $this->readerPreviewUnavailableMessage($state),
            ]);
        }

        $this->digitalAssetService->incrementViewCount($digital_asset);

        $payload = $this->digitalAssetPreviewDisplayService->readerPreviewPayload(
            $book,
            $digital_asset,
            $backUrl
        );
        $payload['preview_state'] = DigitalAssetPreviewStatus::Ready->value;
        $payload['preview_message'] = null;

        return Inertia::render('Reader/BookDigitalPreview', $payload);
    }

    private function readerPreviewUnavailableMessage(string $state): string
    {
        return match ($state) {
            DigitalAssetPreviewStatus::Pending->value,
            DigitalAssetPreviewStatus::Processing->value => __('Đang tạo bản xem trước. Vui lòng quay lại sau vài phút.'),
            DigitalAssetPreviewStatus::Failed->value => __('Không tạo được bản xem trước. Vui lòng thử lại sau hoặc liên hệ thư viện.'),
            default => __('Hiện chưa có bản xem trước cho tài liệu này.'),
        };
    }

    public function catalogDigitalPreviewPageImage(Book $book, DigitalAsset $digital_asset, int $page)
    {
        if ((int) $digital_asset->book_id !== (int) $book->id) {
            abort(404);
        }

        return $this->digitalAssetPreviewDisplayService
            ->streamPreviewPageImage($book, $digital_asset, $page);
    }

    /**
     * Tải file PDF gốc (session auth) — chỉ khi đã có quyền tải.
     */
    public function catalogDigitalDownloadPdf(Request $request, Book $book, DigitalAsset $digital_asset)
    {
        if ((int) $digital_asset->book_id !== (int) $book->id) {
            abort(404);
        }

        $user = $request->user();
        if ($user === null) {
            abort(401, __('Vui lòng đăng nhập.'));
        }

        if (! $this->digitalPaywallService->userCanDownloadPdf((int) $user->id, $digital_asset)) {
            abort(403, __('Vui lòng thanh toán để tải toàn bộ nội dung.'));
        }

        if ($digital_asset->embargo_until && $digital_asset->embargo_until > now()->toDateString()) {
            abort(403, __('Tài liệu này chưa mở truy cập.'));
        }

        $this->digitalAssetService->incrementDownloadCount($digital_asset);

        $safeFilename = $this->digitalAssetService->buildPdfDownloadFilename($digital_asset, $book);

        return $this->digitalAssetService->streamPdfDownloadResponse($digital_asset, $safeFilename);
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
        $readerHasLibraryCardRecord = false;
        $user = request()->user();
        if ($user !== null) {
            $readerHasLibraryCardRecord = LibraryCard::query()
                ->where('user_id', $user->id)
                ->exists();
        }

        return Inertia::render('Reader/Services/Index', [
            'reader_has_library_card_record' => $readerHasLibraryCardRecord,
        ]);
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
                'library_card_can_reapply' => false,
            ]);
        }

        $card = LibraryCard::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->with(['faculty:id,code,name', 'period:id,code,name'])
            ->first();

        $avatar = $user->avatar;
        if (! empty($avatar) && ! str_starts_with((string) $avatar, 'http')) {
            /** @var FilesystemAdapter $mediaStorage */
            $mediaStorage = Storage::disk((string) config('filesystems.media_disk', 'public'));
            $avatar = $mediaStorage->url((string) $avatar);
        }

        $libraryCardCanReapply = $card === null
            && LibraryCard::onlyTrashed()
                ->where('user_id', $user->id)
                ->exists();

        return Inertia::render('Reader/Services/LibraryCard', [
            'auth_required' => false,
            'card' => $card ? (new LibraryCardResource($card))->resolve() : null,
            'library_card_can_reapply' => $libraryCardCanReapply,
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

    public function servicesBookCart(Request $request): Response|RedirectResponse
    {
        if ($request->query('checkout') === '1' && $request->filled('buy_asset')) {
            $buyAsset = (int) $request->query('buy_asset');
            if ($buyAsset > 0) {
                return redirect()->route('reader.services.digital-payment', ['buy_asset' => $buyAsset]);
            }
        }

        $tab = $request->query('tab', 'borrow');
        if (! in_array($tab, ['borrow', 'purchase'], true)) {
            $tab = 'borrow';
        }

        $user = $request->user();
        $libraryCardPhone = null;
        if ($user !== null) {
            $raw = LibraryCard::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->value('phone');
            if ($raw !== null && trim((string) $raw) !== '') {
                $libraryCardPhone = trim((string) $raw);
            }
        }

        $digitalPurchaseCartItems = null;
        if ($tab === 'purchase' && $user !== null) {
            $digitalPurchaseCartItems = DigitalPurchaseCartItemResource::collection(
                $this->digitalPurchaseCartService->listDigitalItemsForUser($user)
            )->resolve();
        }

        return Inertia::render('Reader/Services/BookCart', [
            'borrow_permissions' => $this->readerBorrowPermissions($user),
            'cart_tab' => $tab,
            'library_card_phone' => $libraryCardPhone,
            'digital_purchase_cart_items' => $digitalPurchaseCartItems,
            'payment_checkout_only' => false,
        ]);
    }

    /** Thanh toán trực tiếp tài liệu số (mua ngay) — không qua màn giỏ hàng. */
    public function servicesDigitalOrders(): Response
    {
        return Inertia::render('Reader/Orders/Index');
    }

    public function servicesDigitalPayment(Request $request): Response
    {
        $user = $request->user();
        $libraryCardPhone = null;
        if ($user !== null) {
            $raw = LibraryCard::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->value('phone');
            if ($raw !== null && trim((string) $raw) !== '') {
                $libraryCardPhone = trim((string) $raw);
            }
        }

        return Inertia::render('Reader/Services/BookCart', [
            'borrow_permissions' => $this->readerBorrowPermissions($user),
            'cart_tab' => 'purchase',
            'library_card_phone' => $libraryCardPhone,
            'digital_purchase_cart_items' => null,
            'payment_checkout_only' => true,
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
