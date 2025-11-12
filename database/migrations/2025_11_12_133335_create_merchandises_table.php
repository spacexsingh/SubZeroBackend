<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('merchandises', function (Blueprint $table) {
            $table->id();
            $table->string('code')
                ->unique();
            $table->string('name');
            $table->unsignedInteger('points_cost');
            $table->unsignedInteger('stock')
                ->nullable();
            $table->json('meta')
                ->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('merchandises');
    }
};
