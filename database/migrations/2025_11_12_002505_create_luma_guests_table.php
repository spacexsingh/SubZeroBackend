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
        Schema::create('luma_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('luma_event_id')->constrained('luma_events')->cascadeOnDelete();
            $table->string('guest_id')->unique(); // gst-xxx from Luma
            $table->string('luma_user_id'); // usr-xxx from Luma
            $table->string('approval_status')->default('pending');
            $table->string('user_name');
            $table->string('user_first_name');
            $table->string('user_last_name');
            $table->string('user_email');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->json('registration_answers')->nullable();
            $table->json('event_tickets')->nullable();
            $table->json('event_ticket')->nullable();
            $table->json('event_ticket_orders')->nullable();
            $table->json('raw_data')->nullable(); // Store complete guest object
            $table->timestamps();

            $table->index(['luma_event_id', 'guest_id']);
            $table->index('user_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luma_guests');
    }
};
