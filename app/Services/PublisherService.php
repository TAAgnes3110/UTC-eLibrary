<?php

namespace App\Services;

use App\Models\Publisher;
use Illuminate\Database\Eloquent\Collection;

class PublisherService
{
    /** @return Collection<int, Publisher> */
    public function listForApi(): Collection
    {
        return Publisher::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
