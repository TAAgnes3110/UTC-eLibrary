<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryService
{
    public function listForApi(): Collection
    {
        return Category::query()
            ->where('is_active', true)
            ->withCount('books')
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'params', 'order'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description ?? '',
                'type' => $c->params['type'] ?? 'category',
                'count' => $c->books_count ?? 0,
            ]);
    }

    public function adminList(string $tab = 'category'): array
    {
        $items = Category::query()
            ->withCount('books')
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'params'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description ?? '',
                'type' => $c->params['type'] ?? 'category',
                'count' => $c->books_count ?? 0,
            ]);
        return ['categories' => $items, 'tab' => $tab];
    }
    
    /**
     * Gợi ý thể loại theo từ khóa (autocomplete).
     * Nhập 1 chữ hay bất kì → trả gợi ý ngay; không có kết quả → trả mảng rỗng.
     *
     * @return array<array{id: int, name: string, code: string}>
     */
    public function searchCategory(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }
        return Category::query()
            ->where('is_active', true)
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'code', 'name'])
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code])
            ->values()
            ->toArray();
    }
}
