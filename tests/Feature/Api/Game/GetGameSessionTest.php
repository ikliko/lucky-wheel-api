<?php

use App\Models\SpinPlan;
use Illuminate\Support\Str;

test('get session returns recovery payload without last round when fresh', function () {
    $boot = bootstrapSession();

    $this->withToken($boot['token'])
        ->getJson('/api/game/session/'.$boot['sessionId'])
        ->assertOk()
        ->assertJsonPath('session.id', $boot['sessionId'])
        ->assertJsonPath('session.configVersion', '1.0.0')
        ->assertJsonPath('session.status', 'active')
        ->assertJsonPath('session.mainSpinsPlayed', 0)
        ->assertJsonPath('session.spinsRemainingInPlan', 10)
        ->assertJsonPath('user.id', $boot['userId'])
        ->assertJsonPath('user.balanceCents', 100000)
        ->assertJsonPath('lastRound', null)
        ->assertJsonMissingPath('session.sequence');
});

test('get session returns last round for replay after a spin', function () {
    $boot = bootstrapSession();

    SpinPlan::query()->where('game_session_id', $boot['sessionId'])->update([
        'sequence' => array_merge(['sector_5'], array_fill(0, 9, 'sector_0')),
        'next_index' => 0,
    ]);

    $spinResponse = spin($boot['token'], $boot['sessionId'], 200)->assertOk();

    $this->withToken($boot['token'])
        ->getJson('/api/game/session/'.$boot['sessionId'])
        ->assertOk()
        ->assertJsonPath('session.mainSpinsPlayed', 1)
        ->assertJsonPath('session.spinsRemainingInPlan', 9)
        ->assertJsonPath('user.balanceCents', $spinResponse->json('balanceAfterRoundCents'))
        ->assertJsonPath('lastRound.roundId', $spinResponse->json('roundId'))
        ->assertJsonPath('lastRound.betCents', 200)
        ->assertJsonPath('lastRound.winCents', $spinResponse->json('winCents'))
        ->assertJsonPath('lastRound.result.sectorId', 'sector_5')
        ->assertJsonPath('lastRound.result.type', 'win')
        ->assertJsonMissingPath('lastRound.plan');
});

test('get session forbids other users', function () {
    $owner = bootstrapSession();
    $intruder = bootstrapSession();

    $this->withToken($intruder['token'])
        ->getJson('/api/game/session/'.$owner['sessionId'])
        ->assertStatus(403)
        ->assertJsonPath('error.code', 'SESSION_FORBIDDEN');
});

test('get missing session returns 404', function () {
    $boot = bootstrapSession();

    $this->withToken($boot['token'])
        ->getJson('/api/game/session/'.Str::uuid())
        ->assertStatus(404)
        ->assertJsonPath('error.code', 'SESSION_NOT_FOUND');
});

test('get session requires authentication', function () {
    $this->getJson('/api/game/session/'.Str::uuid())
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');
});
