<?php

namespace Tests\Feature\Modules;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\ModuleTestHelpers;
use Tests\TestCase;

/**
 * Module: Thủ thư — loans admin (10 case).
 */
class AdminLoanModuleTest extends TestCase
{
    use ModuleTestHelpers;
    use RefreshDatabase;

    #[Test]
    public function case01_unauthenticated_loans_list_returns_401(): void
    {
        $this->getJson('/api/v1/loans')->assertStatus(401);
    }

    #[Test]
    public function case02_student_cannot_list_admin_loans(): void
    {
        [, $h] = $this->studentContext();
        $this->getJson('/api/v1/loans', $h)->assertStatus(403);
    }

    #[Test]
    public function case03_librarian_can_list_loans(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/loans', $h)->assertStatus(200);
    }

    #[Test]
    public function case04_per_page_999_returns_422(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/loans?per_page=999', $h)->assertStatus(422);
    }

    #[Test]
    public function case05_invalid_sort_returns_422(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/loans?sort=DROP TABLE', $h)->assertStatus(422);
    }

    #[Test]
    public function case06_show_nonexistent_loan_returns_404(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/loans/9999999', $h)->assertStatus(404);
    }

    #[Test]
    public function case07_bulk_delete_empty_ids_returns_422(): void
    {
        [, $h] = $this->librarianContext();
        $this->postJson('/api/v1/loans/bulk-delete', ['ids' => []], $h)->assertStatus(422);
    }

    #[Test]
    public function case08_bulk_return_missing_return_date_returns_422(): void
    {
        [, $h] = $this->librarianContext();
        $this->postJson('/api/v1/loans/bulk-return', ['loan_ids' => [1]], $h)->assertStatus(422);
    }

    #[Test]
    public function case09_statistics_invalid_granularity_returns_422(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/loans/statistics?granularity=weekly', $h)->assertStatus(422);
    }

    #[Test]
    public function case10_borrow_requests_list_returns_200(): void
    {
        [, $h] = $this->librarianContext();
        $this->getJson('/api/v1/loans/borrow-requests', $h)->assertStatus(200);
    }
}
