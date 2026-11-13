<?php

use App\Game\Config\GameConfiguration;

test('game configuration loads wheel-18 map from config', function () {
    $config = new GameConfiguration(
        require dirname(__DIR__, 4).'/config/wheel.php'
    );

    expect($config->version())->toBe('1.0.0')
        ->and($config->sectors())->toHaveCount(18)
        ->and($config->specialAId())->toBe('sector_1')
        ->and($config->specialBId())->toBe('sector_4')
        ->and($config->betOptionsCents())->toHaveCount(28)
        ->and($config->quickBetsCents())->toBe([10, 50, 100, 200, 500, 1000])
        ->and($config->freeSpinsAward())->toBe(3)
        ->and($config->freeSpinsRetriggerLimit())->toBe(1)
        ->and($config->bonusBaseMultiplier())->toBe(2)
        ->and($config->bonusCollectionMultipliers())->toBe([
            'star' => 2,
            'diamond' => 3,
            'circle' => 5,
        ])
        ->and($config->sectorIndexById('sector_7'))->toBe(7)
        ->and($config->isAllowedBet(200))->toBeTrue()
        ->and($config->isAllowedBet(15))->toBeFalse();
});
