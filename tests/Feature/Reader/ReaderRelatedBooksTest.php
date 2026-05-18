<?php

namespace Tests\Feature\Reader;

use App\Enums\ResourceType;
use App\Models\Author;
use App\Models\Book;
use App\Models\Classification;
use App\Services\BookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReaderRelatedBooksTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reader_related_books_prefers_same_classification_and_author(): void
    {
        $classification = Classification::query()->create([
            'code' => 'REL-01',
            'name' => 'Phân loại liên quan',
        ]);
        $author = Author::query()->create(['name' => 'Nguyễn Văn A', 'slug' => 'nguyen-van-a']);

        $current = Book::query()->create([
            'title' => 'Sách gốc',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);
        $current->authors()->attach($author->id, ['order' => 1]);

        $bestMatch = Book::query()->create([
            'title' => 'Sách cùng phân loại và tác giả',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);
        $bestMatch->authors()->attach($author->id, ['order' => 1]);

        $otherClassification = Classification::query()->create([
            'code' => 'REL-99',
            'name' => 'Khác',
        ]);
        Book::query()->create([
            'title' => 'Sách khác phân loại',
            'classification_id' => $otherClassification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);

        $current->load('authors', 'classification');
        $related = app(BookService::class)->readerRelatedBooks($current, 6);

        $this->assertGreaterThanOrEqual(1, $related->count());
        $this->assertSame((int) $bestMatch->id, (int) $related->first()->id);
        $this->assertFalse($related->contains(fn (Book $b) => (int) $b->id === (int) $current->id));
    }

    #[Test]
    public function book_show_page_includes_related_books_section(): void
    {
        $classification = Classification::query()->create([
            'code' => 'REL-02',
            'name' => 'Nhóm A',
        ]);

        $book = Book::query()->create([
            'title' => 'Đầu mục chính',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::REFERENCE->value,
            'access_mode' => 'circulation_only',
            'quantity' => 2,
        ]);

        Book::query()->create([
            'title' => 'Đầu mục liên quan',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::REFERENCE->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);

        $this->get(route('reader.catalog.show', ['book' => $book->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reader/BookShow')
                ->has('related_books', 1)
                ->where('related_books.0.title', 'Đầu mục liên quan'));
    }

    #[Test]
    public function related_books_page_lists_matches_without_current_book(): void
    {
        $classification = Classification::query()->create([
            'code' => 'REL-04',
            'name' => 'Nhóm C',
        ]);

        $book = Book::query()->create([
            'title' => 'Sách nguồn',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);

        $related = Book::query()->create([
            'title' => 'Sách liên quan trang riêng',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);

        $this->get(route('reader.catalog.related', ['book' => $book->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reader/RelatedBooks')
                ->where('source_book.id', $book->id)
                ->where('source_book.title', 'Sách nguồn')
                ->has('books.data', 1)
                ->where('books.data.0.id', $related->id)
                ->where('books.data.0.title', 'Sách liên quan trang riêng'));
    }

    #[Test]
    public function related_books_page_supports_pagination(): void
    {
        $classification = Classification::query()->create([
            'code' => 'REL-05',
            'name' => 'Nhóm D',
        ]);

        $book = Book::query()->create([
            'title' => 'Sách nguồn phân trang',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);

        for ($i = 1; $i <= 14; $i++) {
            Book::query()->create([
                'title' => "Sách liên quan {$i}",
                'classification_id' => $classification->id,
                'resource_type' => ResourceType::TEXTBOOK->value,
                'access_mode' => 'circulation_only',
                'quantity' => 1,
            ]);
        }

        $this->get(route('reader.catalog.related', ['book' => $book->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reader/RelatedBooks')
                ->where('books.current_page', 1)
                ->where('books.last_page', 2)
                ->where('books.per_page', 12)
                ->where('books.total', 14)
                ->has('books.data', 12));

        $this->get(route('reader.catalog.related', ['book' => $book->id, 'page' => 2]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reader/RelatedBooks')
                ->where('books.current_page', 2)
                ->has('books.data', 2));
    }

    #[Test]
    public function current_book_is_never_in_related_list(): void
    {
        $classification = Classification::query()->create([
            'code' => 'REL-03',
            'name' => 'Nhóm B',
        ]);

        $book = Book::query()->create([
            'title' => 'Chỉ một cuốn',
            'classification_id' => $classification->id,
            'resource_type' => ResourceType::TEXTBOOK->value,
            'access_mode' => 'circulation_only',
            'quantity' => 1,
        ]);

        $book->load('authors', 'classification');
        $related = app(BookService::class)->readerRelatedBooks($book, 12);

        $this->assertCount(0, $related);
    }
}
