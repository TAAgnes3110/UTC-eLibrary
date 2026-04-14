<?php

namespace Tests\Feature\Frontend;

use App\Enums\ResourceType;
use App\Models\Book;
use App\Models\SavedBook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedBooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_save_and_unsave_book_on_book_detail(): void
    {
        $user = User::factory()->create();
        $book = Book::query()->create([
            'title' => 'Test book',
            'quantity' => 1,
            'resource_type' => ResourceType::REFERENCE,
        ]);

        $this->actingAs($user)
            ->post(route('reader.saved-books.store', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('saved_books', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user)
            ->delete(route('reader.saved-books.destroy', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('saved_books', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_saved_books_page_lists_saved_items(): void
    {
        $user = User::factory()->create();
        $book = Book::query()->create([
            'title' => 'Listed book',
            'quantity' => 2,
            'resource_type' => ResourceType::REFERENCE,
        ]);
        SavedBook::query()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reader.saved-books'));

        $response->assertOk();
        $response->assertSee('Listed book', false);
    }
}
