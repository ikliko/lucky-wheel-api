<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'is_guest',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable {
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasUuids;

    public function gameSessions(): HasMany {
        return $this->hasMany(GameSession::class);
    }

    public function gameRounds(): HasMany {
        return $this->hasMany(GameRound::class);
    }

    public function balanceTransactions(): HasMany {
        return $this->hasMany(BalanceTransaction::class);
    }

    protected function casts(): array {
        return [
            'is_guest' => 'boolean',
            'balance_cents' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
