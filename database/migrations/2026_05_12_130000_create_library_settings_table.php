<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('type', 20)->default('string')->comment('string|int|bool|json');
            $table->text('value')->nullable();
            $table->json('json_value')->nullable();
            $table->timestamps();

            $table->userAuditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_settings');
    }
};
