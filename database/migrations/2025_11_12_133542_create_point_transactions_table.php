<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('type', ['earn', 'spend']);
            $table->integer('points');
            $table->foreignId('point_action_id')
                ->nullable()
                ->constrained('point_actions')
                ->nullOnDelete();
            $table->foreignId('merchandise_id')
                ->nullable()
                ->constrained('merchandises')
                ->nullOnDelete();
            $table->json('meta')
                ->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->unique(['user_id', 'point_action_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('point_transactions');
    }
};
