
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'game_round_id',
    'type',
    'amount_cents',
    'balance_after_cents',
])]
class BalanceTransaction extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'balance_after_cents' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameRound(): BelongsTo
    {
        return $this->belongsTo(GameRound::class);
    }
}
