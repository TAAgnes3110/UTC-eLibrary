<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thesis_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->unique()->constrained('books')->cascadeOnDelete();
            $table->string('work_type', 40)->index();
            $table->string('degree_program', 150)->nullable();
            $table->string('supervisor_name')->nullable();
            $table->unsignedInteger('supervisor_user_id')->nullable()->index();
            $table->unsignedSmallInteger('defense_year')->nullable();
            $table->text('keywords')->nullable();
            $table->text('abstract_text')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Audit columns
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();

            $table->foreign('supervisor_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thesis_metadata');
    }
};
