<?php

namespace App\Services;

use App\Helpers\FileHelpers;
use App\Models\NewsAttachment;
use App\Models\NewsPost;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsPostService
{
    public const READER_NEWS_CACHE_VERSION_KEY = 'reader:news:cache-version';

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
                'content',
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
            ->with(['attachments', 'createdBy:id,name,email'])
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
                $thumbnailPath = FileHelpers::replaceUploadedFile($post->thumbnail_path, $thumbnail, 'public', 'upload/news/thumbnails');
            } elseif ((bool) ($data['remove_thumbnail'] ?? false) && $thumbnailPath !== null) {
                FileHelpers::deleteIfExists($thumbnailPath, 'public');
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

        return $updated;
    }

    public function delete(NewsPost $post): void
    {
        $post->update([
            'status' => NewsPost::STATUS_INACTIVE,
        ]);

        $this->bumpReaderNewsCacheVersion();
    }

    public function uploadContentImage(UploadedFile $image): string
    {
        return FileHelpers::storeUploadedFile($image, 'public', 'upload/news/content');
    }

    private function storeThumbnail(?UploadedFile $thumbnail): ?string
    {
        if (! $thumbnail instanceof UploadedFile) {
            return null;
        }

        return FileHelpers::storeUploadedFile($thumbnail, 'public', 'upload/news/thumbnails');
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
            $path = FileHelpers::storeUploadedFile($file, 'public', 'upload/news/attachments');
            NewsAttachment::query()->create([
                'news_post_id' => $post->id,
                'storage_disk' => 'public',
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
            $url = Storage::url((string) $attachment->file_path);
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
}
