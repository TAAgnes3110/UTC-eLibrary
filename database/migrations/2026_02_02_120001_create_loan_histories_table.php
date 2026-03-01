<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_histories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('loan_id');
            $table->string('action', 30)->index();
            $table->unsignedInteger('performed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->json('params')->nullable();
            $table->timestamp('performed_at')->useCurrent();

            $table->index(['loan_id', 'performed_at']);
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_histories');
    }
};
