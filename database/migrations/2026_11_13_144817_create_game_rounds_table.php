<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('game_session_id')
                ->constrained('game_sessions')
                ->cascadeOnDelete();

            $table->foreignUuid('spin_plan_id')
                ->constrained('spin_plans')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('plan_index');

            $table->uuid('idempotency_key');

            $table->unsignedInteger('bet_cents');
            $table->string('sector_id', 64);

            $table->json('outcome');

            $table->unsignedBigInteger('win_cents')
                ->default(0);

            $table->unsignedBigInteger(
                'balance_before_cents'
            );

            $table->unsignedBigInteger(
                'balance_after_bet_cents'
            );

            $table->unsignedBigInteger(
                'balance_after_round_cents'
            );

            $table->timestamp('settled_at')->nullable();

            $table->timestamps();

            $table->unique([
                'spin_plan_id',
                'plan_index',
            ]);

            $table->unique([
                'game_session_id',
                'idempotency_key',
            ]);

            $table->index([
                'game_session_id',
                'created_at',
            ]);
        });
    }

    public function down(): void {
        Schema::dropIfExists('game_rounds');
    }
};
