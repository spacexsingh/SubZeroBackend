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
        $tables = [
            'luma_api_keys',
            'conferences',
            'side_events',
            'luma_events',
            'luma_guest_wallet_addresses',
            'luma_guest_status_histories',
            'luma_guests',
            'point_transactions',
            'merchandise',
            'point_actions',
            'users',
            'point_balance_snapshots',
            'conference_user',
            'luma_guest_status_history',
            'merchandises',
            'personal_access_tokens',
            'side_event_user',
            'side_events',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'created_at')) {
                        $table->dropColumn('created_at');
                    }
                    if (Schema::hasColumn($tableName, 'updated_at')) {
                        $table->dropColumn('updated_at');
                    }
                    if (Schema::hasColumn($tableName, 'deleted_at')) {
                        $table->dropColumn('deleted_at');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'luma_api_keys',
            'conferences',
            'side_events',
            'luma_events',
            'luma_guest_wallet_addresses',
            'luma_guest_status_histories',
            'luma_guests',
            'point_transactions',
            'merchandise',
            'point_actions',
            'users',
            'point_balance_snapshots',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->timestamps();
                    $table->softDeletes();
                });
            }
        }
    }
};
