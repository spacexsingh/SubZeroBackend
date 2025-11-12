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
        Schema::create('luma_guest_wallet_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('luma_guest_id')->constrained('luma_guests')->cascadeOnDelete();
            $table->string('wallet_address');
            $table->string('wallet_type')->default('polkadot'); // For future support of other wallet types
            $table->timestamps();

            // Unique constraint: one guest can have one wallet address of each type
            $table->unique(['luma_guest_id', 'wallet_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('luma_guest_wallet_addresses');
    }
};
