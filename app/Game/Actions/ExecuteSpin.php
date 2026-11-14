<?php

namespace App\Game\Actions;

use App\Game\Config\GameConfiguration;
use App\Game\Exceptions\GameRuleException;
use App\Game\Mathematics\BonusSequenceGenerator;
use App\Game\Mathematics\FreeSpinsGenerator;
use App\Game\Mathematics\SpinPlanGenerator;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\SpinPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

readonly class ExecuteSpin
{
    public function __construct(
        private GameConfiguration $config,
        private SpinPlanGenerator $planGenerator,
        private FreeSpinsGenerator $freeSpinsGenerator,
        private BonusSequenceGenerator $bonusGenerator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(User $user, string $sessionId, int $betCents, string $idempotencyKey): array
    {
        return DB::transaction(function () use ($user, $sessionId, $betCents, $idempotencyKey): array {
            /** @var User $lockedUser */
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existing = GameRound::query()
                ->where('game_session_id', $sessionId)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                if ($existing->user_id !== $lockedUser->id
                    || $existing->bet_cents !== $betCents) {
                    throw new GameRuleException(
                        'IDEMPOTENCY_CONFLICT',
                        'Idempotency key was already used with a different payload.',
                        409,
                    );
                }

                return $this->toResponse($existing);
            }

            if (! $this->config->isAllowedBet($betCents)) {
                throw new GameRuleException(
                    'INVALID_BET',
                    'Bet amount is not allowed.',
                    422,
                    ['betCents' => $betCents],
                );
            }

            /** @var GameSession|null $session */
            $session = GameSession::query()
                ->whereKey($sessionId)
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                throw new GameRuleException(
                    'SESSION_NOT_FOUND',
                    'Game session was not found.',
                    404,
                );
            }

            if ($session->user_id !== $lockedUser->id) {
                throw new GameRuleException(
                    'SESSION_FORBIDDEN',
                    'You do not own this game session.',
                    403,
                );
            }

            if ($session->status !== 'active') {
                throw new GameRuleException(
                    'SESSION_INACTIVE',
                    'Game session is not active.',
                    409,
                );
            }

            if ($lockedUser->balance_cents < $betCents) {
                throw new GameRuleException(
                    'INSUFFICIENT_BALANCE',
                    'Balance is lower than bet.',
                    422,
                    [
                        'balanceCents' => $lockedUser->balance_cents,
                        'betCents' => $betCents,
                    ],
                );
            }

            $plan = $this->currentPlan($session);

            if ($plan->isExhausted()) {
                $plan = $this->createNextPlan($session, $plan);
            }

            $plan = SpinPlan::query()
                ->whereKey($plan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $planIndex = $plan->next_index;
            $sectorId = $plan->nextSectorId();

            if ($sectorId === null) {
                throw new GameRuleException(
                    'PLAN_EXHAUSTED',
                    'No sector remains in the current spin plan.',
                    409,
                );
            }

            $result = $this->buildResult($sectorId, $betCents);
            $winCents = $this->winCentsFromResult($result);

            $balanceBefore = $lockedUser->balance_cents;
            $balanceAfterBet = $balanceBefore - $betCents;
            $balanceAfterRound = $balanceAfterBet + $winCents;

            $lockedUser->balance_cents = $balanceAfterRound;
            $lockedUser->save();

            $plan->next_index = $planIndex + 1;
            $plan->save();

            if ($plan->isExhausted()) {
                $this->createNextPlan($session, $plan);
            }

            $round = GameRound::query()->create([
                'user_id' => $lockedUser->id,
                'game_session_id' => $session->id,
                'spin_plan_id' => $plan->id,
                'plan_index' => $planIndex,
                'idempotency_key' => $idempotencyKey,
                'bet_cents' => $betCents,
                'sector_id' => $sectorId,
                'outcome' => $result,
                'win_cents' => $winCents,
                'balance_before_cents' => $balanceBefore,
                'balance_after_bet_cents' => $balanceAfterBet,
                'balance_after_round_cents' => $balanceAfterRound,
                'settled_at' => now(),
            ]);

            $round->setRelation('spinPlan', $plan);

            return $this->toResponse($round);
        });
    }

    private function currentPlan(GameSession $session): SpinPlan
    {
        /** @var SpinPlan|null $plan */
        $plan = SpinPlan::query()
            ->where('game_session_id', $session->id)
            ->orderByDesc('plan_number')
            ->first();

        if ($plan === null) {
            throw new GameRuleException(
                'PLAN_MISSING',
                'Spin plan is missing for this session.',
                409,
            );
        }

        return $plan;
    }

    private function createNextPlan(GameSession $session, SpinPlan $exhausted): SpinPlan
    {
        $sequence = $exhausted->sequence ?? [];
        $previousLast = $sequence === [] ? null : $sequence[array_key_last($sequence)];

        return SpinPlan::query()->create([
            'game_session_id' => $session->id,
            'plan_number' => $exhausted->plan_number + 1,
            'sequence' => $this->planGenerator->generate($previousLast),
            'next_index' => 0,
            'config_version' => $this->config->version(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildResult(string $sectorId, int $betCents): array
    {
        $sector = $this->config->sectorById($sectorId);
        $type = (string) $sector['type'];
        $sectorIndex = $this->config->sectorIndexById($sectorId);

        $result = [
            'sectorId' => $sectorId,
            'sectorIndex' => $sectorIndex,
            'type' => $type,
        ];

        if ($type === 'win') {
            $result['winCents'] = (int) round($betCents * (float) $sector['multiplier']);
        } elseif ($type === 'bonus') {
            $bonus = $this->bonusGenerator->generate($betCents);
            $result['bonus'] = $bonus;
            $result['winCents'] = $bonus['finalWinCents'];
        } elseif ($type === 'free_spins') {
            $freeSpins = $this->freeSpinsGenerator->generate($betCents);
            $result['freeSpins'] = $freeSpins;
            $result['winCents'] = $freeSpins['totalWinCents'];
        } else {
            $result['winCents'] = 0;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function winCentsFromResult(array $result): int
    {
        return (int) ($result['winCents'] ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function toResponse(GameRound $round): array
    {
        $plan = $round->relationLoaded('spinPlan')
            ? $round->spinPlan
            : $round->spinPlan()->firstOrFail();

        $spinsRemaining = max(
            0,
            count($plan->sequence ?? []) - ($round->plan_index + 1),
        );

        return [
            'roundId' => $round->id,
            'sessionId' => $round->game_session_id,
            'configVersion' => $plan->config_version,
            'betCents' => $round->bet_cents,
            'balanceBeforeCents' => $round->balance_before_cents,
            'balanceAfterBetCents' => $round->balance_after_bet_cents,
            'balanceAfterRoundCents' => $round->balance_after_round_cents,
            'winCents' => $round->win_cents,
            'result' => $round->outcome,
            'plan' => [
                'planNumber' => $plan->plan_number,
                'spinNumber' => $round->plan_index + 1,
                'spinsRemaining' => $spinsRemaining,
            ],
        ];
    }
}
