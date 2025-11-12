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
        Schema::table('luma_guest_status_history', function (Blueprint $table) {
            $table->boolean('is_current')->default(false)->after('notes');
            $table->index(['luma_guest_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('luma_guest_status_history', function (Blueprint $table) {
            $table->dropIndex(['luma_guest_id', 'is_current']);
            $table->dropColumn('is_current');
        });
    }
};
