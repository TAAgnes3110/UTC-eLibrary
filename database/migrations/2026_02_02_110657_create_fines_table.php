<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('loan_id');
            $table->unsignedInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->string('reason', 20);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('unpaid')->index();
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedInteger('processed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('params')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
