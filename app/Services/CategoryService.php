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

    public function adminPageData(string $tab = 'category'): array
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
}
