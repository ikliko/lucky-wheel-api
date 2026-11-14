<?php

use App\Models\GameRound;
use App\Models\SpinPlan;
use App\Models\User;
use Illuminate\Support\Str;

function bootstrapSession(): array
{
    $response = test()->postJson('/api/game/session')->assertCreated();

    return [
        'token' => $response->json('accessToken'),
        'sessionId' => $response->json('session.id'),
        'userId' => $response->json('user.id'),
    ];
}

function spin(string $token, string $sessionId, int $betCents, ?string $idempotencyKey = null)
{
    return test()
        ->withToken($token)
        ->withHeader('Idempotency-Key', $idempotencyKey ?? (string) Str::uuid())
        ->postJson('/api/game/spin', [
            'sessionId' => $sessionId,
            'betCents' => $betCents,
        ]);
}

test('unauthenticated spin returns 401', function () {
    $this->postJson('/api/game/spin', [
        'sessionId' => (string) Str::uuid(),
        'betCents' => 100,
    ], [
        'Idempotency-Key' => (string) Str::uuid(),
    ])->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED')
        ->assertJsonMissingPath('message')
        ->assertJsonMissingPath('exception')
        ->assertJsonMissingPath('trace');
});

test('invalid bearer token returns clean 401 json', function () {
    $this->withToken('not-a-real-token')
        ->withHeader('Idempotency-Key', (string) Str::uuid())
        ->post('/api/game/spin', [
            'sessionId' => (string) Str::uuid(),
            'betCents' => 100,
        ])
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED')
        ->assertJsonMissingPath('exception')
        ->assertJsonMissingPath('trace');
});

test('spin settles bet and advances plan once', function () {
    ['token' => $token, 'sessionId' => $sessionId, 'userId' => $userId] = bootstrapSession();

    $plan = SpinPlan::query()->where('game_session_id', $sessionId)->firstOrFail();
    $plan->update([
        'sequence' => array_merge(
            ['sector_5'],
            array_fill(0, 9, 'sector_0'),
        ),
        'next_index' => 0,
    ]);

    $response = spin($token, $sessionId, 200)->assertOk();

    $response->assertJsonPath('sessionId', $sessionId)
        ->assertJsonPath('betCents', 200)
        ->assertJsonPath('balanceBeforeCents', 100000)
        ->assertJsonPath('balanceAfterBetCents', 99800)
        ->assertJsonPath('winCents', 200)
        ->assertJsonPath('balanceAfterRoundCents', 100000)
        ->assertJsonPath('result.sectorId', 'sector_5')
        ->assertJsonPath('result.type', 'win')
        ->assertJsonPath('plan.planNumber', 1)
        ->assertJsonPath('plan.spinNumber', 1)
        ->assertJsonPath('plan.spinsRemaining', 9);

    expect(User::query()->findOrFail($userId)->balance_cents)->toBe(100000)
        ->and(SpinPlan::query()->findOrFail($plan->id)->next_index)->toBe(1)
        ->and(GameRound::query()->count())->toBe(1);
});

test('duplicate idempotency key returns same round without double debit', function () {
    ['token' => $token, 'sessionId' => $sessionId, 'userId' => $userId] = bootstrapSession();

    SpinPlan::query()->where('game_session_id', $sessionId)->update([
        'sequence' => array_merge(['sector_0'], array_fill(0, 9, 'sector_3')),
        'next_index' => 0,
    ]);

    $key = (string) Str::uuid();

    $first = spin($token, $sessionId, 100, $key)->assertOk();
    $second = spin($token, $sessionId, 100, $key)->assertOk();

    expect($second->json('roundId'))->toBe($first->json('roundId'))
        ->and($second->json())->toBe($first->json())
        ->and(GameRound::query()->count())->toBe(1)
        ->and(User::query()->findOrFail($userId)->balance_cents)->toBe(99900)
        ->and(SpinPlan::query()->where('game_session_id', $sessionId)->value('next_index'))->toBe(1);
});

test('same idempotency key with different bet returns 409', function () {
    ['token' => $token, 'sessionId' => $sessionId] = bootstrapSession();

    SpinPlan::query()->where('game_session_id', $sessionId)->update([
        'sequence' => array_merge(['sector_0'], array_fill(0, 9, 'sector_3')),
        'next_index' => 0,
    ]);

    $key = (string) Str::uuid();

    spin($token, $sessionId, 100, $key)->assertOk();

    spin($token, $sessionId, 200, $key)
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'IDEMPOTENCY_CONFLICT');
});

test('insufficient balance returns 422', function () {
    ['token' => $token, 'sessionId' => $sessionId, 'userId' => $userId] = bootstrapSession();

    User::query()->whereKey($userId)->update(['balance_cents' => 50]);

    spin($token, $sessionId, 100)
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'INSUFFICIENT_BALANCE');

    expect(GameRound::query()->count())->toBe(0);
});

test('invalid bet returns 422', function () {
    ['token' => $token, 'sessionId' => $sessionId] = bootstrapSession();

    spin($token, $sessionId, 15)
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'INVALID_BET');
});

test('spinning another users session returns 403', function () {
    $owner = bootstrapSession();
    $intruder = bootstrapSession();

    spin($intruder['token'], $owner['sessionId'], 100)
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'SESSION_FORBIDDEN');
});

test('bonus base spin nests bonus payload and credits final win only', function () {
    ['token' => $token, 'sessionId' => $sessionId, 'userId' => $userId] = bootstrapSession();

    SpinPlan::query()->where('game_session_id', $sessionId)->update([
        'sequence' => array_merge(['sector_11'], array_fill(0, 9, 'sector_0')),
        'next_index' => 0,
    ]);

    $response = spin($token, $sessionId, 200)->assertOk();

    expect($response->json('result.type'))->toBe('bonus')
        ->and($response->json('result.bonus.finalWinCents'))->toBe($response->json('winCents'))
        ->and($response->json('balanceAfterRoundCents'))
        ->toBe(100000 - 200 + $response->json('winCents'))
        ->and(User::query()->findOrFail($userId)->balance_cents)
        ->toBe($response->json('balanceAfterRoundCents'));
});

test('exhausting a plan creates the next plan', function () {
    ['token' => $token, 'sessionId' => $sessionId] = bootstrapSession();

    $plan = SpinPlan::query()->where('game_session_id', $sessionId)->firstOrFail();
    $plan->update([
        'sequence' => array_fill(0, 10, 'sector_0'),
        'next_index' => 9,
    ]);

    spin($token, $sessionId, 10)->assertOk()
        ->assertJsonPath('plan.spinNumber', 10)
        ->assertJsonPath('plan.spinsRemaining', 0);

    expect(SpinPlan::query()->where('game_session_id', $sessionId)->count())->toBe(2)
        ->and(SpinPlan::query()->where('game_session_id', $sessionId)->where('plan_number', 2)->value('next_index'))
        ->toBe(0);
});
