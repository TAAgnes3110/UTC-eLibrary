<?php

namespace App\Console\Commands;

use App\Models\Book;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncBookCoversToR2Command extends Command
{
    protected $signature = 'media:sync-book-covers
        {--chunk=20 : So sach moi lo}
        {--limit=500 : Gioi han tong so sach can quet}
        {--offset=0 : Vi tri bat dau}
        {--only-default : Chi cap nhat sach dang dung anh bia mac dinh}
        {--force : Ghi de ca sach da co cover_image hop le}';

    protected $description = 'Dong bo anh bia sach len media disk theo ISBN, Open Library va Internet Archive.';

    /** @var array<int, array<string, mixed>>|null */
    private ?array $otlIndex = null;

    public function handle(): int
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        $chunk = max(5, (int) $this->option('chunk'));
        $limit = max(1, (int) $this->option('limit'));
        $offset = max(0, (int) $this->option('offset'));
        $force = (bool) $this->option('force');
        $onlyDefault = (bool) $this->option('only-default');

        $query = Book::query()
            ->with(['authors:id,name'])
            ->select(['id', 'title', 'book_code', 'registration_number', 'summary', 'cover_image', 'updated_at'])
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit);

        if ($onlyDefault) {
            $query->where(function ($q): void {
                $q->whereNull('cover_image')
                    ->orWhere('cover_image', '')
                    ->orWhere('cover_image', 'like', '%default-book-cover%');
            });
        }

        $books = $query->get();
        if ($books->isEmpty()) {
            $this->warn('Khong co sach nao trong pham vi can quet.');

            return self::SUCCESS;
        }

        $this->info("Quet {$books->count()} sach | disk={$disk} | chunk={$chunk}");

        $updated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($books->chunk($chunk) as $batch) {
            /** @var Book $book */
            foreach ($batch as $book) {
                $currentPath = trim((string) ($book->cover_image ?? ''));
                if (! $force && $this->hasValidCover($currentPath, $disk)) {
                    $skipped++;

                    continue;
                }

                $author = $this->primaryAuthorName($book);
                $isbn = $this->resolveIsbn($book);
                $result = $this->fetchCover((string) $book->title, $author, $isbn, (int) $book->id);

                if ($result === null) {
                    $failed++;
                    $this->line("  FAIL #{$book->id} {$book->title}");

                    continue;
                }

                [$imageBinary, $imageExt, $targetPath] = $result;

                try {
                    Storage::disk($disk)->put($targetPath, $imageBinary, [
                        'visibility' => 'public',
                        'ContentType' => $imageExt === 'png' ? 'image/png' : 'image/jpeg',
                    ]);
                    $book->cover_image = $targetPath;
                    $book->save();
                    $updated++;
                    $this->line("  OK   #{$book->id} → {$targetPath}");
                } catch (\Throwable) {
                    $failed++;
                    $this->line("  FAIL #{$book->id} upload");
                }

                usleep(200_000);
            }
        }

        $this->info("Done. updated={$updated}, skipped={$skipped}, failed={$failed}");

        return self::SUCCESS;
    }

    private function hasValidCover(string $path, string $disk): bool
    {
        if ($path === '' || str_contains($path, 'default-book-cover')) {
            return false;
        }

        return Storage::disk($disk)->exists($path);
    }

    private function primaryAuthorName(Book $book): ?string
    {
        $name = trim((string) ($book->authors->first()?->name ?? ''));
        if ($name === '' || str_starts_with($name, 'OTL ID')) {
            return null;
        }

        return $name;
    }

    private function resolveIsbn(Book $book): ?string
    {
        $fromCode = $this->extractIsbn((string) $book->book_code);
        if ($fromCode !== null) {
            return $fromCode;
        }

        $summary = (string) ($book->summary ?? '');
        if (preg_match('/\*\*ISBN-13:\*\*\s*(\d{10,13})/u', $summary, $m) === 1) {
            return $m[1];
        }
        if (preg_match('/ISBN-13[^:]*:\s*(\d{10,13})/ui', $summary, $m) === 1) {
            return $m[1];
        }

        $otlId = $this->extractOtlId((string) $book->registration_number);
        if ($otlId !== null) {
            $otl = $this->otlIndex()[$otlId] ?? null;
            $isbn = trim((string) ($otl['ISBN13'] ?? ''));
            if ($isbn !== '' && preg_match('/^\d{10,13}$/', $isbn) === 1) {
                return $isbn;
            }
        }

        return null;
    }

    private function extractOtlId(string $registrationNumber): ?int
    {
        if (preg_match('/^OTL-(\d+)$/i', trim($registrationNumber), $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function otlIndex(): array
    {
        if ($this->otlIndex !== null) {
            return $this->otlIndex;
        }

        $cachePath = storage_path('app/cache/otl_api_index.json');
        if (is_readable($cachePath)) {
            $cached = json_decode((string) file_get_contents($cachePath), true);
            if (is_array($cached) && count($cached) > 100) {
                $this->otlIndex = $cached;

                return $this->otlIndex;
            }
        }

        $index = [];
        $page = 1;
        $totalPages = 1;
        while ($page <= $totalPages) {
            $response = Http::timeout(30)->get('https://open.umn.edu/opentextbooks/textbooks.json?page='.$page);
            if (! $response->successful()) {
                break;
            }
            $totalPages = (int) ($response->json('links.total_pages') ?? $totalPages);
            foreach ($response->json('data') ?? [] as $item) {
                if (isset($item['id'])) {
                    $index[(int) $item['id']] = $item;
                }
            }
            $page++;
            usleep(150_000);
        }

        if ($index !== []) {
            @mkdir(dirname($cachePath), 0755, true);
            file_put_contents($cachePath, json_encode($index, JSON_UNESCAPED_UNICODE));
        }

        $this->otlIndex = $index;

        return $this->otlIndex;
    }

    /**
     * @return array{0: string, 1: 'jpg'|'png', 2: string}|null
     */
    private function fetchCover(string $title, ?string $author, ?string $isbn, int $bookId): ?array
    {
        if ($isbn !== null) {
            [$binary, $ext] = $this->fetchByIsbn($isbn);
            if ($binary !== null) {
                return [$binary, $ext, "utc-elibrary/book-covers/isbn/{$isbn}.{$ext}"];
            }
        }

        [$binary, $ext, $fallbackIsbn] = $this->fetchByOpenLibrary($title, $author);
        if ($binary !== null) {
            if ($fallbackIsbn !== null) {
                return [$binary, $ext, "utc-elibrary/book-covers/isbn/{$fallbackIsbn}.{$ext}"];
            }

            return [$binary, $ext, $this->titleCoverPath($title, $bookId, $ext)];
        }

        [$binary, $ext] = $this->fetchByInternetArchive($title, $author);
        if ($binary !== null) {
            return [$binary, $ext, $this->titleCoverPath($title, $bookId, $ext)];
        }

        [$binary, $ext] = $this->fetchByGoogleBooks($title, $author);
        if ($binary !== null) {
            return [$binary, $ext, $this->titleCoverPath($title, $bookId, $ext)];
        }

        return null;
    }

    private function titleCoverPath(string $title, int $bookId, string $ext): string
    {
        $slug = Str::slug($title);
        if ($slug === '') {
            $slug = 'book-'.$bookId;
        }

        return "utc-elibrary/book-covers/title/{$slug}-{$bookId}.{$ext}";
    }

    private function extractIsbn(string $bookCode): ?string
    {
        $trimmed = trim($bookCode);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/GT-ISBN(\d{10,13})/i', $trimmed, $m) === 1) {
            return $m[1];
        }

        if (preg_match('/\b(\d{10,13})\b/', $trimmed, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * @return array{0: string|null, 1: 'jpg'|'png'}
     */
    private function fetchByIsbn(string $isbn): array
    {
        try {
            $img = $this->downloadImage("https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg?default=false");
        } catch (\Throwable) {
            return [null, 'jpg'];
        }
        if ($img !== null) {
            return $img;
        }

        return [null, 'jpg'];
    }

    /**
     * @return array{0: string|null, 1: 'jpg'|'png', 2: string|null}
     */
    private function fetchByOpenLibrary(string $title, ?string $author): array
    {
        $params = ['limit' => 3];
        if ($author !== null) {
            $params['title'] = $title;
            $params['author'] = $author;
        } else {
            $params['q'] = $title;
        }

        try {
            $response = Http::timeout(12)->retry(1, 150)->get('https://openlibrary.org/search.json', $params);
        } catch (\Throwable) {
            return [null, 'jpg', null];
        }

        if (! $response->successful()) {
            return [null, 'jpg', null];
        }

        foreach ($response->json('docs') ?? [] as $doc) {
            $coverId = $doc['cover_i'] ?? null;
            if (is_numeric($coverId)) {
                $img = $this->downloadImage('https://covers.openlibrary.org/b/id/'.(int) $coverId.'-L.jpg');
                if ($img !== null) {
                    $isbnList = $doc['isbn'] ?? [];
                    $fallbackIsbn = null;
                    if (is_array($isbnList) && isset($isbnList[0]) && preg_match('/^\d{10,13}$/', (string) $isbnList[0]) === 1) {
                        $fallbackIsbn = (string) $isbnList[0];
                    }

                    return [$img[0], $img[1], $fallbackIsbn];
                }
            }

            $isbnList = $doc['isbn'] ?? [];
            if (is_array($isbnList)) {
                foreach ($isbnList as $candidate) {
                    if (! preg_match('/^\d{10,13}$/', (string) $candidate)) {
                        continue;
                    }
                    [$binary, $ext] = $this->fetchByIsbn((string) $candidate);
                    if ($binary !== null) {
                        return [$binary, $ext, (string) $candidate];
                    }
                }
            }
        }

        return [null, 'jpg', null];
    }

    /**
     * @return array{0: string|null, 1: 'jpg'|'png'}
     */
    private function fetchByInternetArchive(string $title, ?string $author): array
    {
        $queries = [];
        $safeTitle = str_replace('"', '', $title);
        if ($author !== null) {
            $firstAuthor = trim(explode(',', $author)[0]);
            $queries[] = 'title:"'.$safeTitle.'" AND creator:"'.str_replace('"', '', $firstAuthor).'"';
            $lastName = $this->authorLastName($firstAuthor);
            if ($lastName !== null) {
                $queries[] = 'title:"'.$safeTitle.'" AND creator:"'.$lastName.'"';
            }
        }
        $queries[] = 'title:"'.$safeTitle.'"';

        foreach ($queries as $query) {
            try {
                $response = Http::timeout(15)->retry(1, 150)->get('https://archive.org/advancedsearch.php', [
                    'q' => $query,
                    'fl[]' => 'identifier',
                    'rows' => 5,
                    'output' => 'json',
                ]);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            foreach ($response->json('response.docs') ?? [] as $doc) {
                $identifier = trim((string) ($doc['identifier'] ?? ''));
                if ($identifier === '') {
                    continue;
                }
                $img = $this->downloadImage('https://archive.org/services/img/'.$identifier);
                if ($img !== null) {
                    return $img;
                }
            }
        }

        return [null, 'jpg'];
    }

    private function authorLastName(string $author): ?string
    {
        $author = trim($author);
        if ($author === '') {
            return null;
        }

        $parts = preg_split('/\s+/u', $author) ?: [];
        $last = trim((string) end($parts));

        return $last !== '' ? $last : null;
    }

    /**
     * @return array{0: string|null, 1: 'jpg'|'png'}
     */
    private function fetchByGoogleBooks(string $title, ?string $author): array
    {
        $query = $author !== null ? 'intitle:'.$title.' inauthor:'.$author : 'intitle:'.$title;
        try {
            $response = Http::timeout(12)->retry(0, 0)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => $query,
                'maxResults' => 1,
            ]);
        } catch (\Throwable) {
            return [null, 'jpg'];
        }

        if (! $response->successful()) {
            return [null, 'jpg'];
        }

        $items = $response->json('items');
        if (! is_array($items) || $items === []) {
            return [null, 'jpg'];
        }

        $imgUrl = (string) (data_get($items[0], 'volumeInfo.imageLinks.large')
            ?: data_get($items[0], 'volumeInfo.imageLinks.thumbnail')
            ?: '');
        if ($imgUrl === '') {
            return [null, 'jpg'];
        }

        $img = $this->downloadImage($imgUrl);

        return $img ?? [null, 'jpg'];
    }

    /**
     * @return array{0: string, 1: 'jpg'|'png'}|null
     */
    private function downloadImage(string $url): ?array
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'UTC-eLibrary/1.0'])
                ->retry(1, 120)
                ->get($url);
        } catch (\Throwable) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (! str_contains($contentType, 'image/')) {
            return null;
        }
        $ext = str_contains($contentType, 'png') ? 'png' : 'jpg';
        $body = $response->body();
        if ($body === '' || strlen($body) < 800) {
            return null;
        }

        return [$body, $ext];
    }
}
