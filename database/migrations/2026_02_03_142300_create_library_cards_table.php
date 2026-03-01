<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_number')->unique();
            $table->unsignedInteger('user_id');
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_active')->default(true);
            $table->string('card_type', 30)->default('STANDARD');
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_cards');
    }
};
