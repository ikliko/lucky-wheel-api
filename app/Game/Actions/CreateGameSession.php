<?php

namespace App\Game\Actions;

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\SpinPlanGenerator;
use App\Models\GameSession;
use App\Models\SpinPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

readonly class CreateGameSession
{
    public function __construct(
        private GameConfiguration $config,
        private SpinPlanGenerator $planGenerator,
    ) {}

    /**
     * @return array{
     *     user: User,
     *     session: GameSession,
     *     plan: SpinPlan,
     *     accessToken: string
     * }
     */
    public function handle(): array
    {
        return DB::transaction(function (): array {
            $user = User::query()->create([
                'name' => 'Guest',
                'email' => 'guest-'.Str::uuid().'@guest.local',
                'password' => Str::password(32),
                'is_guest' => true,
                'balance_cents' => $this->config->initialBalanceCents(),
            ]);

            $session = GameSession::query()->create([
                'user_id' => $user->id,
                'config_version' => $this->config->version(),
                'status' => 'active',
                'expires_at' => now()->addDays(7),
            ]);

            $sequence = $this->planGenerator->generate();

            $plan = SpinPlan::query()->create([
                'game_session_id' => $session->id,
                'plan_number' => 1,
                'sequence' => $sequence,
                'next_index' => 0,
                'config_version' => $this->config->version(),
            ]);

            $accessToken = $user->createToken('game')->plainTextToken;

            return [
                'user' => $user,
                'session' => $session,
                'plan' => $plan,
                'accessToken' => $accessToken,
            ];
        });
    }
}
