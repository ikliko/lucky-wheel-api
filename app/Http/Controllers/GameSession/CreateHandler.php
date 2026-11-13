<?php

namespace App\Http\Controllers\GameSession;

use App\Game\Actions\CreateGameSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CreateHandler extends Controller
{
    public function __invoke(CreateGameSession $action): JsonResponse
    {
        $result = $action->handle();

        $plan = $result['plan'];
        $remaining = count($plan->sequence) - $plan->next_index;

        return response()->json([
            'user' => [
                'id' => $result['user']->id,
                'balanceCents' => $result['user']->balance_cents,
            ],
            'session' => [
                'id' => $result['session']->id,
                'configVersion' => $result['session']->config_version,
                'mainSpinsPlayed' => 0,
                'spinsRemainingInPlan' => $remaining,
            ],
            'accessToken' => $result['accessToken'],
        ], 201);
    }
}
