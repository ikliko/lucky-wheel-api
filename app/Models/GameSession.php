<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'config_version',
    'status',
    'expires_at',
])]
class GameSession extends Model {
    use HasUuids;

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function spinPlans(): HasMany {
        return $this->hasMany(SpinPlan::class);
    }

    public function rounds(): HasMany {
        return $this->hasMany(GameRound::class);
    }

    protected function casts(): array {
        return [
            'expires_at' => 'datetime',
        ];
    }
}
