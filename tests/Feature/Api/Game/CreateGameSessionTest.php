<?php

use App\Models\SpinPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('post game session creates guest user session plan and token', function () {
    $response = $this->postJson('/api/game/session');

    $response->assertCreated()
        ->assertJsonStructure([
            'user' => ['id', 'balanceCents'],
            'session' => ['id', 'configVersion', 'mainSpinsPlayed', 'spinsRemainingInPlan'],
            'accessToken',
        ])
        ->assertJsonPath('user.balanceCents', 100000)
        ->assertJsonPath('session.configVersion', '1.0.0')
        ->assertJsonPath('session.mainSpinsPlayed', 0)
        ->assertJsonPath('session.spinsRemainingInPlan', 10);

    $userId = $response->json('user.id');
    $sessionId = $response->json('session.id');
    $token = $response->json('accessToken');

    expect($userId)->toBeString()
        ->and($sessionId)->toBeString()
        ->and($token)->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'id' => $userId,
        'is_guest' => true,
        'balance_cents' => 100000,
    ]);

    $this->assertDatabaseHas('game_sessions', [
        'id' => $sessionId,
        'user_id' => $userId,
        'config_version' => '1.0.0',
        'status' => 'active',
    ]);

    $plan = SpinPlan::query()
        ->where('game_session_id', $sessionId)
        ->where('plan_number', 1)
        ->first();

    expect($plan)->not->toBeNull()
        ->and($plan->next_index)->toBe(0)
        ->and($plan->sequence)->toHaveCount(10)
        ->and($plan->config_version)->toBe('1.0.0');

    $this->withToken($token)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('id', $userId);
});

test('each post game session creates a new guest identity', function () {
    $first = $this->postJson('/api/game/session')->assertCreated();
    $second = $this->postJson('/api/game/session')->assertCreated();

    expect($first->json('user.id'))->not->toBe($second->json('user.id'))
        ->and(User::query()->where('is_guest', true)->count())->toBe(2)
        ->and(DB::table('spin_plans')->count())->toBe(2);
});
