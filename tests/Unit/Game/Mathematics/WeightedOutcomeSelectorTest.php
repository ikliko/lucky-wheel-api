<?php

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\Rng\SeedableRng;
use App\Game\Mathematics\WeightedOutcomeSelector;

function wheelConfig(): GameConfiguration {
    return new GameConfiguration(
        require dirname(__DIR__, 4) . '/config/wheel.php'
    );
}

test('weighted selector returns only non-A/B sector ids', function () {
    $config = wheelConfig();
    $rng = new SeedableRng(42);
    $selector = new WeightedOutcomeSelector($config, $rng);

    $a = $config->specialAId();
    $b = $config->specialBId();
    $allowed = array_values(array_filter(
        array_column($config->sectors(), 'id'),
        fn(string $id): bool => $id !== $a && $id !== $b,
    ));

    for ($i = 0; $i < 200; $i++) {
        $id = $selector->select();
        expect($id)->toBeIn($allowed)
            ->and($id)->not->toBe($a)
            ->and($id)->not->toBe($b);
    }
});

test('weighted selector is deterministic for a fixed seed', function () {
    $config = wheelConfig();

    $first = new WeightedOutcomeSelector($config, new SeedableRng(99));
    $second = new WeightedOutcomeSelector($config, new SeedableRng(99));

    $a = [];
    $b = [];

    for ($i = 0; $i < 20; $i++) {
        $a[] = $first->select();
        $b[] = $second->select();
    }

    expect($a)->toBe($b);
});
