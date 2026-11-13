<?php

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\Rng\RngInterface;
use App\Game\Mathematics\Rng\SeedableRng;
use App\Game\Mathematics\SpinPlanGenerator;
use App\Game\Mathematics\WeightedOutcomeSelector;

/**
 * Forces the first nextInt(0, 5) mask pick, then delegates to SeedableRng.
 */
class MaskForcingRng implements RngInterface {
    private SeedableRng $inner;

    private bool $maskConsumed = false;

    public function __construct(
        private readonly int $maskIndex,
        int                  $seed = 1,
    ) {
        $this->inner = new SeedableRng($seed);
    }

    public function nextInt(int $min, int $max): int {
        if (!$this->maskConsumed && $min === 0 && $max === 5) {
            $this->maskConsumed = true;

            return $this->maskIndex;
        }

        return $this->inner->nextInt($min, $max);
    }

    public function nextFloat(): float {
        return $this->inner->nextFloat();
    }
}

function makePlanGenerator(int $seed): SpinPlanGenerator {
    $config = new GameConfiguration(
        require dirname(__DIR__, 4) . '/config/wheel.php'
    );
    $rng = new SeedableRng($seed);

    return new SpinPlanGenerator(
        $config,
        new WeightedOutcomeSelector($config, $rng),
        $rng,
    );
}

test('generated plans satisfy A/B counts and no adjacency', function () {
    $config = new GameConfiguration(
        require dirname(__DIR__, 4) . '/config/wheel.php'
    );
    $a = $config->specialAId();
    $b = $config->specialBId();

    for ($seed = 1; $seed <= 100; $seed++) {
        $plan = makePlanGenerator($seed)->generate();

        expect($plan)->toHaveCount(10);

        $countA = count(array_filter($plan, fn(string $id): bool => $id === $a));
        $countB = count(array_filter($plan, fn(string $id): bool => $id === $b));

        expect($countA)->toBe(3)
            ->and($countB)->toBe(2);

        for ($i = 0; $i < 9; $i++) {
            $leftSpecial = $plan[$i] === $a || $plan[$i] === $b;
            $rightSpecial = $plan[$i + 1] === $a || $plan[$i + 1] === $b;

            expect($leftSpecial && $rightSpecial)->toBeFalse();
        }

        foreach ($plan as $id) {
            expect(array_column($config->sectors(), 'id'))->toContain($id);
        }
    }
});

test('boundary forbids special first slot when previous plan ended on A/B', function () {
    $config = new GameConfiguration(
        require dirname(__DIR__, 4) . '/config/wheel.php'
    );
    $a = $config->specialAId();
    $b = $config->specialBId();

    foreach ([$a, $b] as $previous) {
        for ($seed = 1; $seed <= 50; $seed++) {
            $plan = makePlanGenerator($seed)->generate($previous);

            expect($plan[0] === $a || $plan[0] === $b)->toBeFalse();

            for ($i = 0; $i < 9; $i++) {
                $leftSpecial = $plan[$i] === $a || $plan[$i] === $b;
                $rightSpecial = $plan[$i + 1] === $a || $plan[$i + 1] === $b;
                expect($leftSpecial && $rightSpecial)->toBeFalse();
            }
        }
    }
});

test('spin plan generator is deterministic for a fixed seed', function () {
    expect(makePlanGenerator(123)->generate())
        ->toBe(makePlanGenerator(123)->generate());
});

test('each of the six legal masks can produce a valid plan', function () {
    $config = new GameConfiguration(
        require dirname(__DIR__, 4) . '/config/wheel.php'
    );
    $a = $config->specialAId();
    $b = $config->specialBId();

    $expectedMasks = [
        [0, 2, 4, 6, 8],
        [0, 2, 4, 6, 9],
        [0, 2, 4, 7, 9],
        [0, 2, 5, 7, 9],
        [0, 3, 5, 7, 9],
        [1, 3, 5, 7, 9],
    ];

    foreach ($expectedMasks as $maskIndex => $expectedMask) {
        $rng = new MaskForcingRng($maskIndex, seed: 7);
        $generator = new SpinPlanGenerator(
            $config,
            new WeightedOutcomeSelector($config, $rng),
            $rng,
        );

        $plan = $generator->generate();

        $actualMask = [];
        foreach ($plan as $index => $id) {
            if ($id === $a || $id === $b) {
                $actualMask[] = $index;
            }
        }

        expect($actualMask)->toBe($expectedMask);
    }
});
