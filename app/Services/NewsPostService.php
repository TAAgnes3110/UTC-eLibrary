<?php

namespace App\Services;

use App\Enums\UploadDirectory;
use App\Helpers\FileHelpers;
use App\Models\NewsAttachment;
use App\Models\NewsPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NewsPostService
{
    private function mediaDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    public const READER_NEWS_CACHE_VERSION_KEY = 'reader:news:cache-version';

    public const ADMIN_NEWS_LIST_CACHE_VERSION_KEY = 'admin:news:list-cache-version';

    private const ATTACHMENTS_SECTION_START = '<!-- news-attachments:start -->';

    private const ATTACHMENTS_SECTION_END = '<!-- news-attachments:end -->';

    public function paginatePublicPublished(array $filters, int $perPage = 15, bool $includeAttachments = true): LengthAwarePaginator
    {
        $relations = $includeAttachments
            ? ['attachments', 'createdBy:id,name,email']
            : ['createdBy:id,name,email'];

        $query = NewsPost::query()
            ->select([
                'id',
                'slug',
                'title',
                'thumbnail_path',
                'type',
                'status',
                'published_at',
                'created_by',
            ])
            ->with($relations)
            ->where('status', NewsPost::STATUS_ACTIVE);

        $type = trim((string) ($filters['type'] ?? ''));
        if (in_array($type, NewsPost::types(), true)) {
            $query->where('type', $type);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $searchIn = $this->resolveSearchIn((array) ($filters['search_in'] ?? []));
            $query->where(function ($q) use ($keyword, $searchIn): void {
                $applied = false;
                if (in_array('title', $searchIn, true)) {
                    $q->where('title', 'like', "%{$keyword}%");
                    $applied = true;
                }
                if (in_array('content', $searchIn, true)) {
                    $method = $applied ? 'orWhere' : 'where';
                    $q->{$method}('content', 'like', "%{$keyword}%");
                }
            });
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('published_at')->orderBy('id');
        } else {
            $query->orderByDesc('published_at')->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findPublishedBySlug(string $slug): ?NewsPost
    {
        return NewsPost::query()
            ->with(['attachments', 'createdBy:id,name,email'])
            ->where('status', NewsPost::STATUS_ACTIVE)
            ->where('slug', $slug)
            ->first();
    }

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = NewsPost::query()
            ->select([
                'id',
                'slug',
                'title',
                'thumbnail_path',
                'type',
                'status',
                'published_at',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->with(['createdBy:id,name,email'])
            ->where('status', NewsPost::STATUS_ACTIVE);

        $type = trim((string) ($filters['type'] ?? ''));
        if (in_array($type, NewsPost::types(), true)) {
            $query->where('type', $type);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        $searchIn = $this->resolveSearchIn((array) ($filters['search_in'] ?? []));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword, $searchIn): void {
                $applied = false;
                if (in_array('title', $searchIn, true)) {
                    $q->where('title', 'like', "%{$keyword}%");
                    $applied = true;
                }
                if (in_array('content', $searchIn, true)) {
                    $method = $applied ? 'orWhere' : 'where';
                    $q->{$method}('content', 'like', "%{$keyword}%");
                }
            });
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        if ($sort === 'oldest') {
            $query->orderBy('id');
        } else {
            $query->orderByDesc('id');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array{title:string,content:string,status?:string}  $data
     * @param  list<UploadedFile>  $attachments
     */
    public function create(array $data, array $attachments = [], ?UploadedFile $thumbnail = null): NewsPost
    {
        $created = DB::transaction(function () use ($data, $attachments, $thumbnail): NewsPost {
            $post = NewsPost::query()->create([
                'slug' => $this->generateUniqueSlug((string) $data['title']),
                'title' => trim((string) $data['title']),
                'content' => trim((string) $data['content']),
                'thumbnail_path' => $this->storeThumbnail($thumbnail),
                'status' => (string) ($data['status'] ?? NewsPost::STATUS_ACTIVE),
                'type' => (string) ($data['type'] ?? NewsPost::TYPE_NEWS),
                'published_at' => now(),
            ]);

            $this->storeAttachments($post, $attachments);
            $this->syncContentWithAttachments($post);

            return $post->fresh(['attachments', 'createdBy:id,name,email']);
        });

        $this->bumpReaderNewsCacheVersion();
        $this->bumpAdminNewsListCacheVersion();

        return $created;
    }

    /**
     * @param  array{title:string,content:string,status?:string,remove_attachment_ids?:array<int,int>,remove_thumbnail?:bool}  $data
     * @param  list<UploadedFile>  $newAttachments
     */
    public function update(NewsPost $post, array $data, array $newAttachments = [], ?UploadedFile $thumbnail = null): NewsPost
    {
        $updated = DB::transaction(function () use ($post, $data, $newAttachments, $thumbnail): NewsPost {
            $thumbnailPath = $post->thumbnail_path;
            if ($thumbnail instanceof UploadedFile) {
                $thumbnailPath = FileHelpers::replaceUploadedFile($post->thumbnail_path, $thumbnail, $this->mediaDisk(), UploadDirectory::newsThumbnails());
            } elseif ((bool) ($data['remove_thumbnail'] ?? false) && $thumbnailPath !== null) {
                FileHelpers::deleteIfExists($thumbnailPath, $this->mediaDisk());
                $thumbnailPath = null;
            }

            $post->update([
                'slug' => $this->generateUniqueSlug((string) $data['title'], $post->id),
                'title' => trim((string) $data['title']),
                'content' => trim((string) $data['content']),
                'thumbnail_path' => $thumbnailPath,
                'status' => (string) ($data['status'] ?? $post->status),
                'type' => (string) ($data['type'] ?? $post->type ?: NewsPost::TYPE_NEWS),
                'published_at' => now(),
            ]);

            $this->removeAttachments($post, array_values(array_map('intval', (array) ($data['remove_attachment_ids'] ?? []))));
            $this->storeAttachments($post, $newAttachments);
            $this->syncContentWithAttachments($post);

            return $post->fresh(['attachments', 'createdBy:id,name,email']);
        });

        $this->bumpReaderNewsCacheVersion();
        $this->bumpAdminNewsListCacheVersion();

        return $updated;
    }

    public function delete(NewsPost $post): void
    {
        $post->update([
            'status' => NewsPost::STATUS_INACTIVE,
        ]);

        $this->bumpReaderNewsCacheVersion();
        $this->bumpAdminNewsListCacheVersion();
    }

    public function uploadContentImage(UploadedFile $image): string
    {
        return FileHelpers::storeUploadedFile($image, $this->mediaDisk(), UploadDirectory::newsContentImages());
    }

    public function updateThumbnailImage(NewsPost $post, UploadedFile $file): NewsPost
    {
        FileHelpers::updateModelImage(
            $post,
            $file,
            'news_posts',
            'thumbnail_path',
            'news-'.$post->id,
            $this->mediaDisk(),
            UploadDirectory::newsThumbnails()
        );

        $this->bumpReaderNewsCacheVersion();
        $this->bumpAdminNewsListCacheVersion();

        return $post->fresh(['attachments', 'createdBy:id,name,email']);
    }

    /**
     * @param  list<int>|null  $onlyPostIds
     * @return array{updated:int, skipped:int, selected_count?: int, selected_missing?: int}
     */
    public function bulkUpdateThumbnailFromZip(UploadedFile $zipFile, ?array $onlyPostIds = null): array
    {
        $tmpDir = FileHelpers::extractZipToTemp($zipFile, 'news-thumbnails');
        $updated = 0;
        $skipped = 0;
        $updatedPostIds = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tmpDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if (! $fileInfo->isFile()) {
                    continue;
                }
                if (FileHelpers::shouldSkipZipExtractedFile($fileInfo)) {
                    $skipped++;

                    continue;
                }
                $ext = strtolower($fileInfo->getExtension() ?: '');
                if (! in_array($ext, FileHelpers::IMAGE_EXTENSIONS, true)) {
                    $skipped++;

                    continue;
                }

                $code = trim($fileInfo->getBasename('.'.$ext));
                if ($code === '') {
                    $skipped++;

                    continue;
                }

                $post = $this->resolvePostByZipCode($code);
                if (! $post) {
                    $skipped++;

                    continue;
                }
                if ($onlyPostIds !== null && $onlyPostIds !== [] && ! in_array((int) $post->id, $onlyPostIds, true)) {
                    $skipped++;

                    continue;
                }

                $uploaded = new UploadedFile(
                    $fileInfo->getPathname(),
                    $fileInfo->getBasename(),
                    FileHelpers::mimeForImageExtension($ext),
                    null,
                    true
                );

                try {
                    $this->updateThumbnailImage($post, $uploaded);
                    $updated++;
                    $updatedPostIds[] = (int) $post->id;
                } catch (\Throwable) {
                    $skipped++;
                }
            }
        } finally {
            FileHelpers::removeDirectory($tmpDir);
        }

        if ($updated > 0) {
            $this->bumpReaderNewsCacheVersion();
            $this->bumpAdminNewsListCacheVersion();
        }
        $out = ['updated' => $updated, 'skipped' => $skipped];
        if ($onlyPostIds !== null && $onlyPostIds !== []) {
            $uniqueUpdated = array_values(array_unique($updatedPostIds));
            $out['selected_count'] = count($onlyPostIds);
            $out['selected_missing'] = count(array_diff($onlyPostIds, $uniqueUpdated));
        }

        return $out;
    }

    private function storeThumbnail(?UploadedFile $thumbnail): ?string
    {
        if (! $thumbnail instanceof UploadedFile) {
            return null;
        }

        return FileHelpers::storeUploadedFile($thumbnail, $this->mediaDisk(), UploadDirectory::newsThumbnails());
    }

    private function resolvePostByZipCode(string $rawCode): ?NewsPost
    {
        $normalized = trim($rawCode);
        $normalized = ltrim($normalized, '#');
        if ($normalized === '') {
            return null;
        }

        if (ctype_digit($normalized)) {
            return NewsPost::query()->find((int) $normalized);
        }

        if (preg_match('/(\d+)/', $normalized, $m) === 1) {
            return NewsPost::query()->find((int) $m[1]);
        }

        return null;
    }

    private function generateUniqueSlug(string $title, ?int $ignorePostId = null): string
    {
        $baseSlug = Str::slug(trim($title));
        if ($baseSlug === '') {
            $baseSlug = 'tin-tuc';
        }

        $slug = $baseSlug;
        $counter = 1;
        while ($this->slugExists($slug, $ignorePostId)) {
            $counter++;
            $slug = $baseSlug.'-'.$counter;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignorePostId = null): bool
    {
        $query = NewsPost::query()->where('slug', $slug);
        if ($ignorePostId !== null) {
            $query->whereKeyNot($ignorePostId);
        }

        return $query->exists();
    }

    /**
     * @param  array<int,mixed>  $values
     * @return list<string>
     */
    private function resolveSearchIn(array $values): array
    {
        $searchIn = array_values(array_filter($values, static fn ($v): bool => is_string($v) && $v !== ''));
        if ($searchIn === []) {
            return ['title', 'content'];
        }

        return $searchIn;
    }

    /**
     * @param  list<UploadedFile>  $attachments
     */
    private function storeAttachments(NewsPost $post, array $attachments): void
    {
        foreach ($attachments as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $disk = $this->mediaDisk();
            $path = FileHelpers::storeUploadedFile($file, $disk, UploadDirectory::newsAttachments());
            NewsAttachment::query()->create([
                'news_post_id' => $post->id,
                'storage_disk' => $disk,
                'file_path' => $path,
                'original_name' => (string) $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'byte_size' => $file->getSize(),
            ]);
        }
    }

    /**
     * @param  list<int>  $attachmentIds
     */
    private function removeAttachments(NewsPost $post, array $attachmentIds): void
    {
        if ($attachmentIds === []) {
            return;
        }

        $toDelete = $post->attachments()->whereIn('id', $attachmentIds)->get();
        foreach ($toDelete as $attachment) {
            FileHelpers::deleteIfExists($attachment->file_path, (string) $attachment->storage_disk);
            $attachment->delete();
        }
    }

    private function syncContentWithAttachments(NewsPost $post): void
    {
        $post->loadMissing('attachments');

        $baseContent = $this->stripGeneratedAttachmentsSection((string) $post->content);
        if ($post->attachments->isEmpty()) {
            $post->update(['content' => $baseContent]);

            return;
        }

        $itemsHtml = '';
        foreach ($post->attachments as $attachment) {
            $disk = (string) ($attachment->storage_disk ?: $this->mediaDisk());
            $url = FileHelpers::rootRelativeMediaUrl((string) $attachment->file_path, $disk) ?? '#';
            $name = e((string) $attachment->original_name);
            $itemsHtml .= "<li><a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\">{$name}</a></li>";
        }

        $attachmentsSection = self::ATTACHMENTS_SECTION_START
            .'<hr /><p><strong>Tệp đính kèm:</strong></p><ul>'
            .$itemsHtml
            .'</ul>'
            .self::ATTACHMENTS_SECTION_END;

        $separator = $baseContent === '' ? '' : "\n\n";
        $post->update([
            'content' => $baseContent.$separator.$attachmentsSection,
        ]);
    }

    private function stripGeneratedAttachmentsSection(string $content): string
    {
        $pattern = '/'.preg_quote(self::ATTACHMENTS_SECTION_START, '/').'.*?'.preg_quote(self::ATTACHMENTS_SECTION_END, '/').'/s';
        $cleaned = preg_replace($pattern, '', $content);

        return trim((string) $cleaned);
    }

    private function bumpReaderNewsCacheVersion(): void
    {
        if (! Cache::has(self::READER_NEWS_CACHE_VERSION_KEY)) {
            Cache::forever(self::READER_NEWS_CACHE_VERSION_KEY, 1);

            return;
        }

        Cache::increment(self::READER_NEWS_CACHE_VERSION_KEY);
    }

    public function adminListCacheVersion(): int
    {
        if (! Cache::has(self::ADMIN_NEWS_LIST_CACHE_VERSION_KEY)) {
            Cache::forever(self::ADMIN_NEWS_LIST_CACHE_VERSION_KEY, 1);

            return 1;
        }

        return (int) Cache::get(self::ADMIN_NEWS_LIST_CACHE_VERSION_KEY, 1);
    }

    private function bumpAdminNewsListCacheVersion(): void
    {
        if (! Cache::has(self::ADMIN_NEWS_LIST_CACHE_VERSION_KEY)) {
            Cache::forever(self::ADMIN_NEWS_LIST_CACHE_VERSION_KEY, 1);

            return;
        }

        Cache::increment(self::ADMIN_NEWS_LIST_CACHE_VERSION_KEY);
    }
}
