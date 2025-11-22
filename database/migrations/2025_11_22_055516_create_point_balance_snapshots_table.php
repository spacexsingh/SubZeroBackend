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
        Schema::create('point_balance_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('luma_event_id')->nullable()->constrained()->onDelete('set null');
            $table->date('snapshot_date');
            $table->integer('earned_points')->default(0);
            $table->integer('redeemed_points')->default(0);
            $table->integer('balance')->default(0);
            $table->string('source')->default('manual'); // 'luma_sync', 'manual', 'excel_import', etc.
            $table->json('meta')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('snapshot_date');
            $table->unique(['user_id', 'snapshot_date', 'luma_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_balance_snapshots');
    }
};
