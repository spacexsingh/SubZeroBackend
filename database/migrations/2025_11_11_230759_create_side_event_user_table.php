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
        Schema::create('side_event_user', function (Blueprint $table) {
            $table->foreignId('side_event_id')->constrained('side_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['site_manager', 'volunteer']);
            $table->timestamps();

            // Composite primary key
            $table->primary(['side_event_id', 'user_id', 'role']);

            // Indexes
            $table->index('side_event_id');
            $table->index('user_id');
            $table->index(['side_event_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('side_event_user');
    }
};
