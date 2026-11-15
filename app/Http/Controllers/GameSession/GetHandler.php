<?php

namespace App\Http\Controllers\GameSession;

use App\Game\Actions\GetGameSession;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetHandler extends Controller
{
    public function __invoke(Request $request, string $session, GetGameSession $action): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json(
            $action->handle($user, $session),
        );
    }
}
