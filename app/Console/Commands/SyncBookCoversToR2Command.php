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
        {--chunk=40 : So sach moi lo}
        {--limit=404 : Gioi han tong so sach can quet}
        {--offset=0 : Vi tri bat dau}
        {--force : Ghi de ca sach da co cover_image}';

    protected $description = 'Dong bo anh bia sach len media disk (R2) theo ISBN va ten sach.';

    public function handle(): int
    {
        $disk = (string) config('filesystems.media_disk', 'public');
        $chunk = max(10, (int) $this->option('chunk'));
        $limit = max(1, (int) $this->option('limit'));
        $offset = max(0, (int) $this->option('offset'));
        $force = (bool) $this->option('force');

        $query = Book::query()
            ->select(['id', 'title', 'book_code', 'cover_image', 'updated_at'])
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit);

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
                $isbn = $this->extractIsbn((string) $book->book_code);
                $currentPath = trim((string) ($book->cover_image ?? ''));
                $isDefaultCover = str_contains($currentPath, '/defaults/default-book-cover.');
                if (! $force && $currentPath !== '' && ! $isDefaultCover && Storage::disk($disk)->exists($currentPath)) {
                    $skipped++;

                    continue;
                }

                $imageBinary = null;
                $imageExt = 'jpg';
                $targetPath = '';

                if ($isbn !== null) {
                    [$imageBinary, $imageExt] = $this->fetchByIsbn($isbn);
                    $targetPath = "utc-elibrary/book-covers/isbn/{$isbn}.{$imageExt}";
                }

                if ($imageBinary === null) {
                    [$imageBinary, $imageExt, $fallbackIsbn] = $this->fetchByTitle((string) $book->title);
                    if ($imageBinary !== null) {
                        if ($fallbackIsbn !== null) {
                            $targetPath = "utc-elibrary/book-covers/isbn/{$fallbackIsbn}.{$imageExt}";
                        } else {
                            $slug = Str::slug((string) $book->title);
                            if ($slug === '') {
                                $slug = 'book-'.$book->id;
                            }
                            $targetPath = "utc-elibrary/book-covers/title/{$slug}-{$book->id}.{$imageExt}";
                        }
                    }
                }

                if ($imageBinary === null || $targetPath === '') {
                    $failed++;

                    continue;
                }

                try {
                    Storage::disk($disk)->put($targetPath, $imageBinary, [
                        'visibility' => 'public',
                        'ContentType' => $imageExt === 'png' ? 'image/png' : 'image/jpeg',
                    ]);
                    $book->cover_image = $targetPath;
                    $book->save();
                    $updated++;
                } catch (\Throwable) {
                    $failed++;
                }
            }
        }

        $this->info("Done. updated={$updated}, skipped={$skipped}, failed={$failed}");

        return self::SUCCESS;
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
        $candidates = [
            "https://covers.openlibrary.org/b/isbn/{$isbn}-L.jpg?default=false",
            "https://www.googleapis.com/books/v1/volumes?q=isbn:{$isbn}&maxResults=1",
        ];

        foreach ($candidates as $url) {
            if (str_contains($url, 'googleapis.com')) {
                $google = Http::timeout(12)->retry(1, 150)->get($url);
                if (! $google->successful()) {
                    continue;
                }
                $items = $google->json('items');
                if (! is_array($items) || $items === []) {
                    continue;
                }
                $thumb = data_get($items[0], 'volumeInfo.imageLinks.thumbnail');
                $large = data_get($items[0], 'volumeInfo.imageLinks.large');
                $imgUrl = (string) ($large ?: $thumb ?: '');
                if ($imgUrl === '') {
                    continue;
                }
                $img = $this->downloadImage($imgUrl);
                if ($img !== null) {
                    return $img;
                }
                continue;
            }

            $img = $this->downloadImage($url);
            if ($img !== null) {
                return $img;
            }
        }

        return [null, 'jpg'];
    }

    /**
     * @return array{0: string|null, 1: 'jpg'|'png', 2: string|null}
     */
    private function fetchByTitle(string $title): array
    {
        $keyword = trim($title);
        if ($keyword === '') {
            return [null, 'jpg', null];
        }

        $openSearch = Http::timeout(12)
            ->retry(1, 150)
            ->get('https://openlibrary.org/search.json', [
                'title' => $keyword,
                'limit' => 1,
            ]);
        if ($openSearch->successful()) {
            $docs = $openSearch->json('docs');
            if (is_array($docs) && isset($docs[0])) {
                $isbnList = data_get($docs[0], 'isbn');
                if (is_array($isbnList) && isset($isbnList[0]) && preg_match('/^\d{10,13}$/', (string) $isbnList[0]) === 1) {
                    $isbn = (string) $isbnList[0];
                    [$img, $ext] = $this->fetchByIsbn($isbn);
                    if ($img !== null) {
                        return [$img, $ext, $isbn];
                    }
                }
            }
        }

        $google = Http::timeout(12)->retry(1, 150)->get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'intitle:'.$keyword,
            'maxResults' => 1,
        ]);
        if (! $google->successful()) {
            return [null, 'jpg', null];
        }

        $items = $google->json('items');
        if (! is_array($items) || $items === []) {
            return [null, 'jpg', null];
        }
        $thumb = data_get($items[0], 'volumeInfo.imageLinks.thumbnail');
        $large = data_get($items[0], 'volumeInfo.imageLinks.large');
        $imgUrl = (string) ($large ?: $thumb ?: '');
        if ($imgUrl === '') {
            return [null, 'jpg', null];
        }
        $img = $this->downloadImage($imgUrl);
        if ($img === null) {
            return [null, 'jpg', null];
        }

        return [$img[0], $img[1], null];
    }

    /**
     * @return array{0: string, 1: 'jpg'|'png'}|null
     */
    private function downloadImage(string $url): ?array
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'UTC-eLibrary/1.0'])
            ->retry(1, 120)
            ->get($url);
        if (! $response->successful()) {
            return null;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (! str_contains($contentType, 'image/')) {
            return null;
        }
        $ext = str_contains($contentType, 'png') ? 'png' : 'jpg';
        $body = $response->body();
        if ($body === '') {
            return null;
        }

        return [$body, $ext];
    }
}

