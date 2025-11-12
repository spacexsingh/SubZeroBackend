<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('luma_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->unique()->constrained('conferences')->cascadeOnDelete();
            $table->text('api_key'); // Encrypted API key
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luma_api_keys');
    }
};
