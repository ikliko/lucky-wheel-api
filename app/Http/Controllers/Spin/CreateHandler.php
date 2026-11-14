<?php

namespace App\Http\Controllers\Spin;

use App\Game\Actions\ExecuteSpin;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Spin\CreateRequest;
use Illuminate\Http\JsonResponse;

class CreateHandler extends Controller
{
    public function __invoke(CreateRequest $request, ExecuteSpin $action): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $payload = $action->handle(
            $user,
            (string) $request->string('sessionId'),
            (int) $request->integer('betCents'),
            (string) $request->string('idempotencyKey'),
        );

        return response()->json($payload);
    }
}
