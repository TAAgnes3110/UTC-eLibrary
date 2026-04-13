<?php

namespace Tests\Feature\Backend;

use App\Enums\LibraryCardStatus;
use App\Models\LibraryCard;
use App\Services\LibraryCard\LibraryCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryCardManagementIndexTest extends TestCase
{
    use ActsAsApiUser;
    use RefreshDatabase;

    /**
     * @return list<array<string, mixed>>
     */
    private function itemsFromLibraryCardsIndexResponse(array $decoded): array
    {
        $d = $decoded['data'] ?? null;
        if (! is_array($d)) {
            return [];
        }
        if (isset($d['data']) && is_array($d['data'])) {
            return $d['data'];
        }
        if ($d !== [] && array_is_list($d)) {
            return $d;
        }

        return [];
    }

    public function test_service_management_index_includes_active_and_pending_pickup(): void
    {
        LibraryCard::query()->create([
            'card_number' => 'SVC-UNPAID-'.uniqid(),
            'code' => 'SVC-UNPAID-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
            'status' => LibraryCardStatus::PENDING,
        ]);

        $active = LibraryCard::query()->create([
            'card_number' => 'SVC-ACT-'.uniqid(),
            'code' => 'SVC-ACT-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        $pickup = LibraryCard::query()->create([
            'card_number' => 'SVC-PU-'.uniqid(),
            'code' => 'SVC-PU-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_PENDING_PICKUP,
            'status' => LibraryCardStatus::PENDING,
        ]);

        $management = app(LibraryCardService::class)->index(null, 50, null, null, null, null, true);
        $ids = collect($management->items())->pluck('id')->all();
        $this->assertContains($active->id, $ids);
        $this->assertContains($pickup->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_management_query_param_lists_active_not_pending_review(): void
    {
        $pending = LibraryCard::query()->create([
            'card_number' => 'MGMT-UNPAID-'.uniqid(),
            'code' => 'MGMT-UNPAID-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_PENDING_REVIEW,
            'status' => LibraryCardStatus::PENDING,
        ]);

        $active = LibraryCard::query()->create([
            'card_number' => 'MGMT-ACT-'.uniqid(),
            'code' => 'MGMT-ACT-'.uniqid(),
            'holder_type' => LibraryCard::HOLDER_TYPE_EXTERNAL,
            'workflow_status' => LibraryCard::WORKFLOW_ACTIVE,
            'status' => LibraryCardStatus::ACTIVE,
        ]);

        [, $token] = $this->createLibrarianUserAndToken();

        $management = $this->getJson(
            '/api/v1/library-cards?management=1&per_page=50',
            $this->apiTokenHeaders($token)
        );
        $management->assertOk();
        $items = $this->itemsFromLibraryCardsIndexResponse($management->json());
        $ids = collect($items)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertContains($active->id, $ids);
        $this->assertNotContains($pending->id, $ids);

        $full = $this->getJson(
            '/api/v1/library-cards?per_page=50',
            $this->apiTokenHeaders($token)
        );
        $full->assertOk();
        $itemsFull = $this->itemsFromLibraryCardsIndexResponse($full->json());
        $idsFull = collect($itemsFull)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertContains($active->id, $idsFull);
        $this->assertContains($pending->id, $idsFull);
    }
}
