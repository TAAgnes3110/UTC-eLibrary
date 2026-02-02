<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('library_settings', function (Blueprint $table) {
      $table->increments('id');
      $table->string('key')->unique();
      $table->text('value')->nullable();
      $table->string('type')->default('string')->comment('string, integer, boolean, json');
      $table->string('group')->default('general');
      $table->text('description')->nullable();
      $table->timestamps();

      $table->index(['group', 'key']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('library_settings');
  }
};
