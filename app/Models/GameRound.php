<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'game_session_id',
    'spin_plan_id',
    'plan_index',
    'idempotency_key',
    'bet_cents',
    'sector_id',
    'outcome',
    'win_cents',
    'balance_before_cents',
    'balance_after_bet_cents',
    'balance_after_round_cents',
    'settled_at',
])]
class GameRound extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'outcome' => 'array',
            'plan_index' => 'integer',
            'bet_cents' => 'integer',
            'win_cents' => 'integer',
            'balance_before_cents' => 'integer',
            'balance_after_bet_cents' => 'integer',
            'balance_after_round_cents' => 'integer',
            'settled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function spinPlan(): BelongsTo
    {
        return $this->belongsTo(SpinPlan::class);
    }

    public function balanceTransactions(): HasMany
    {
        return $this->hasMany(BalanceTransaction::class);
    }
}
