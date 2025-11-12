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
        Schema::create('luma_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('side_event_id')->unique()->constrained('side_events')->cascadeOnDelete();
            $table->string('luma_event_id')->unique(); // evt-xxx from Luma
            $table->string('name');
            $table->string('url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luma_events');
    }
};
