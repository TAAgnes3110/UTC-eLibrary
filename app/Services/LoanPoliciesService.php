<?php

namespace App\Services;

use App\Enums\ResourceType;
use App\Enums\RoleType;
use App\Models\LibraryCard;
use App\Models\LoanPolicy;
use Illuminate\Support\Facades\Cache;

class LoanPoliciesService
{
    private const CACHE_TTL_SECONDS = 300;

    public function resolvePolicyForHolderType(string $holderType): ?LoanPolicy
    {
        $holderType = $this->normalizeHolderType($holderType);

        return Cache::remember(
            $this->policyCacheKey($holderType),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            static fn (): ?LoanPolicy => LoanPolicy::forLibraryHolderType($holderType)
        );
    }

    public function resolvePolicyForCard(LibraryCard $card): ?LoanPolicy
    {
        return $this->resolvePolicyForHolderType((string) $card->holder_type);
    }

    public function getMaxBooksForHolderType(string $holderType, ?ResourceType $resourceType = null): int
    {
        $policy = $this->resolvePolicyForHolderType($holderType);
        if ($policy === null) {
            return 0;
        }

        $params = is_array($policy->params) ? $policy->params : [];
        if ($resourceType === ResourceType::TEXTBOOK && array_key_exists('max_textbooks', $params)) {
            return max(0, (int) $params['max_textbooks']);
        }
        if ($resourceType === ResourceType::REFERENCE && array_key_exists('max_reference', $params)) {
            return max(0, (int) $params['max_reference']);
        }

        return max(0, (int) $policy->max_books);
    }

    /**
     * @return array{max_books:int,max_textbooks:int,max_reference:int}
     */
    public function getBorrowLimitsForHolderType(string $holderType): array
    {
        $holderType = $this->normalizeHolderType($holderType);

        return Cache::remember(
            $this->limitsCacheKey($holderType),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($holderType): array {
                $policy = LoanPolicy::forLibraryHolderType($holderType);
                if ($policy === null) {
                    return [
                        'max_books' => 0,
                        'max_textbooks' => 0,
                        'max_reference' => 0,
                    ];
                }

                $params = is_array($policy->params) ? $policy->params : [];
                $maxBooks = max(0, (int) $policy->max_books);

                return [
                    'max_books' => $maxBooks,
                    'max_textbooks' => array_key_exists('max_textbooks', $params)
                        ? max(0, (int) $params['max_textbooks'])
                        : $maxBooks,
                    'max_reference' => array_key_exists('max_reference', $params)
                        ? max(0, (int) $params['max_reference'])
                        : $maxBooks,
                ];
            }
        );
    }

    /**
     * @return array{allow_home:bool,allow_onsite:bool}
     */
    public function getBorrowPermissionsForHolderType(string $holderType): array
    {
        $holderType = $this->normalizeHolderType($holderType);

        return Cache::remember(
            $this->permissionsCacheKey($holderType),
            now()->addSeconds(self::CACHE_TTL_SECONDS),
            function () use ($holderType): array {
                $policy = LoanPolicy::forLibraryHolderType($holderType);
                if ($policy === null) {
                    return ['allow_home' => false, 'allow_onsite' => false];
                }

                return [
                    'allow_home' => (bool) $policy->allow_home,
                    'allow_onsite' => (bool) $policy->allow_onsite,
                ];
            }
        );
    }

    public function getMaxBooksForCard(LibraryCard $card, ?ResourceType $resourceType = null): int
    {
        return $this->getMaxBooksForHolderType((string) $card->holder_type, $resourceType);
    }

    public function getMaxTextbooksForHolderType(string $holderType): int
    {
        return $this->getMaxBooksForHolderType($holderType, ResourceType::TEXTBOOK);
    }

    public function getMaxReferenceForHolderType(string $holderType): int
    {
        return $this->getMaxBooksForHolderType($holderType, ResourceType::REFERENCE);
    }

    public function getMaxTextbooksForCard(LibraryCard $card): int
    {
        return $this->getMaxTextbooksForHolderType((string) $card->holder_type);
    }

    public function getMaxReferenceForCard(LibraryCard $card): int
    {
        return $this->getMaxReferenceForHolderType((string) $card->holder_type);
    }

    public function create(array $data): LoanPolicy
    {
        $policy = new LoanPolicy;
        $this->assignAttributes($policy, $data);
        $policy->save();
        $this->forgetCacheForPolicy($policy);

        return $policy->fresh();
    }

    public function update(LoanPolicy $loanPolicy, array $data): LoanPolicy
    {
        $this->assignAttributes($loanPolicy, $data);
        $loanPolicy->save();
        $this->forgetCacheForPolicy($loanPolicy);

        return $loanPolicy->fresh();
    }

    private function normalizeHolderType(string $holderType): string
    {
        return strtolower(trim($holderType));
    }

    private function policyCacheKey(string $holderType): string
    {
        return "loan_policies:policy:holder_type:{$holderType}";
    }

    private function limitsCacheKey(string $holderType): string
    {
        return "loan_policies:limits:holder_type:{$holderType}";
    }

    private function permissionsCacheKey(string $holderType): string
    {
        return "loan_policies:permissions:holder_type:{$holderType}";
    }

    private function forgetHolderTypeCache(string $holderType): void
    {
        $holderType = $this->normalizeHolderType($holderType);
        Cache::forget($this->policyCacheKey($holderType));
        Cache::forget($this->limitsCacheKey($holderType));
        Cache::forget($this->permissionsCacheKey($holderType));
    }

    private function forgetCacheForPolicy(LoanPolicy $policy): void
    {
        $holderType = match ((string) ($policy->user_type ?? '')) {
            RoleType::STUDENT->value => LibraryCard::HOLDER_TYPE_STUDENT,
            RoleType::TEACHER->value => LibraryCard::HOLDER_TYPE_TEACHER,
            RoleType::MEMBER->value => LibraryCard::HOLDER_TYPE_EXTERNAL,
            default => null,
        };

        if ($holderType !== null) {
            $this->forgetHolderTypeCache($holderType);

            return;
        }

        // Fallback cho policy mặc định/không rõ mapping: xóa cả 3 nhóm bạn đọc.
        $this->forgetHolderTypeCache(LibraryCard::HOLDER_TYPE_STUDENT);
        $this->forgetHolderTypeCache(LibraryCard::HOLDER_TYPE_TEACHER);
        $this->forgetHolderTypeCache(LibraryCard::HOLDER_TYPE_EXTERNAL);
    }

    /**
     * Gán thuộc tính fillable; MEMBER = bạn đọc ngoài → không mượn về nhà .
     *
     * @param  array<string, mixed>  $data
     */
    private function assignAttributes(LoanPolicy $policy, array $data): void
    {
        $keys = array_flip($policy->getFillable());
        $payload = array_intersect_key($data, $keys);

        if ($payload !== []) {
            $policy->fill($payload);
        }

        if ($policy->allow_onsite === null) {
            $policy->allow_onsite = true;
        }

        $userType = $policy->user_type ?? null;
        if ($userType === RoleType::MEMBER->value) {
            $policy->allow_home = false;
        } elseif ($policy->allow_home === null) {
            $policy->allow_home = true;
        }

        $policy->allow_onsite = (bool) $policy->allow_onsite;
        $policy->allow_home = (bool) $policy->allow_home;

        $this->clampNonNegativeNumerics($policy);
    }

    /**
     * Đảm bảo số liệu nghiệp vụ không âm (bổ sung sau validate).
     */
    private function clampNonNegativeNumerics(LoanPolicy $policy): void
    {
        $policy->max_books = max(0, (int) $policy->max_books);
        $policy->max_days = max(0, (int) $policy->max_days);
        $policy->max_renewals = max(0, (int) $policy->max_renewals);
        $policy->overdue_fine_per_day = max(0, (float) $policy->overdue_fine_per_day);

        if ($policy->params !== null && is_array($policy->params)) {
            $p = $policy->params;
            foreach (['max_textbooks', 'max_reference'] as $key) {
                if (! array_key_exists($key, $p)) {
                    continue;
                }
                $p[$key] = max(0, (int) $p[$key]);
            }
            $policy->params = $p;
        }
    }
}
