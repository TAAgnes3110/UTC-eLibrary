<?php

namespace Database\Seeders;

use App\Models\Publisher;
use Illuminate\Database\Seeder;

class PublisherSeeder extends Seeder
{
    public function run(): void
    {
        if (Publisher::where('name', Publisher::DEFAULT_PUBLISHER_NAME)->exists()) {
            return;
        }

        Publisher::create([
            'name' => Publisher::DEFAULT_PUBLISHER_NAME,
            'code' => 'UTT',
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'contact_person' => null,
            'country' => 'Việt Nam',
            'is_active' => true,
        ]);
    }
}
