<?php

namespace App\Game\Actions;

use App\Game\Exceptions\GameRuleException;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\SpinPlan;
use App\Models\User;

readonly class GetGameSession
{
    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, string $sessionId): array
    {
        /** @var GameSession|null $session */
        $session = GameSession::query()->whereKey($sessionId)->first();

        if ($session === null) {
            throw new GameRuleException(
                'SESSION_NOT_FOUND',
                'Game session was not found.',
                404,
            );
        }

        if ($session->user_id !== $user->id) {
            throw new GameRuleException(
                'SESSION_FORBIDDEN',
                'You do not own this game session.',
                403,
            );
        }

        $user->refresh();

        $plan = SpinPlan::query()
            ->where('game_session_id', $session->id)
            ->orderByDesc('plan_number')
            ->first();

        $spinsRemainingInPlan = 0;
        if ($plan !== null) {
            $spinsRemainingInPlan = max(
                0,
                count($plan->sequence ?? []) - $plan->next_index,
            );
        }

        $mainSpinsPlayed = GameRound::query()
            ->where('game_session_id', $session->id)
            ->count();

        /** @var GameRound|null $lastRound */
        $lastRound = GameRound::query()
            ->where('game_session_id', $session->id)
            ->latest('created_at')
            ->first();

        return [
            'session' => [
                'id' => $session->id,
                'configVersion' => $session->config_version,
                'status' => $session->status,
                'mainSpinsPlayed' => $mainSpinsPlayed,
                'spinsRemainingInPlan' => $spinsRemainingInPlan,
            ],
            'user' => [
                'id' => $user->id,
                'balanceCents' => $user->balance_cents,
            ],
            'lastRound' => $lastRound === null ? null : [
                'roundId' => $lastRound->id,
                'betCents' => $lastRound->bet_cents,
                'balanceBeforeCents' => $lastRound->balance_before_cents,
                'balanceAfterBetCents' => $lastRound->balance_after_bet_cents,
                'balanceAfterRoundCents' => $lastRound->balance_after_round_cents,
                'winCents' => $lastRound->win_cents,
                'result' => $lastRound->outcome,
            ],
        ];
    }
}
