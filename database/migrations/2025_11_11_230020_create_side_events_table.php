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
        Schema::create('side_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->constrained('conferences')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();

            // Dates and times
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            // Location (can differ from main conference)
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
            $table->string('room_number')->nullable();

            // Event details
            $table->string('event_type')->nullable(); // workshop, panel, networking, presentation, etc.

            // Display order
            $table->unsignedInteger('sort_order')->default(0);

            // Creator
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('conference_id');
            $table->index(['conference_id', 'start_datetime']);
            $table->index('start_datetime');
            $table->index('sort_order');
            $table->index('created_by');

            // Unique constraint for slug within a conference
            $table->unique(['conference_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('side_events');
    }
};
