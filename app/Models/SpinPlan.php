<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'game_session_id',
    'plan_number',
    'sequence',
    'next_index',
    'config_version',
])]
class SpinPlan extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'sequence' => 'array',
            'next_index' => 'integer',
            'plan_number' => 'integer',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class);
    }

    public function isExhausted(): bool
    {
        return $this->next_index >= count(
                $this->sequence ?? []
            );
    }

    public function nextSectorId(): ?string
    {
        return $this->sequence[$this->next_index]
            ?? null;
    }
}
