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
        Schema::create('luma_guest_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('luma_guest_id')->constrained('luma_guests')->cascadeOnDelete();
            $table->string('status');
            $table->string('from_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['luma_guest_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luma_guest_status_history');
    }
};