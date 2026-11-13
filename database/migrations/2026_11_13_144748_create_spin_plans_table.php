<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('spin_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('game_session_id')
                ->constrained('game_sessions')
                ->cascadeOnDelete();

            $table->unsignedInteger('plan_number');
            $table->json('sequence');
            $table->unsignedTinyInteger('next_index')
                ->default(0);

            $table->string('config_version', 64);

            $table->timestamps();

            $table->unique([
                'game_session_id',
                'plan_number',
            ]);
        });
    }

    public function down(): void {
        Schema::dropIfExists('spin_plans');
    }
};
