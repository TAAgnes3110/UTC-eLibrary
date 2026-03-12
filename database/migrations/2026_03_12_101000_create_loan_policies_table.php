<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_policies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->index();       
            $table->string('name');                               
            $table->string('user_type', 50)->nullable();          
            $table->unsignedInteger('max_books')->default(0);     
            $table->unsignedInteger('max_days')->default(0);      
            $table->unsignedTinyInteger('max_renewals')->default(0);
            $table->decimal('overdue_fine_per_day', 10, 2)->default(0); 
            $table->boolean('allow_home')->default(true);         
            $table->boolean('allow_onsite')->default(true);       
            $table->json('params')->nullable();                   
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_policies');
    }
};

