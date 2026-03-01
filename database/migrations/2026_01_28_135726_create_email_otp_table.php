<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_otp', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('otp', 10);
            $table->timestamp('expired_at')->nullable()->index();
            $table->json('params')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otp');
    }
};
