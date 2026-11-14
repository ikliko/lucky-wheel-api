<?php

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\BonusSequenceGenerator;
use App\Game\Mathematics\Rng\RngInterface;
use App\Game\Mathematics\Rng\SeedableRng;

function bonusConfig(): GameConfiguration
{
    return new GameConfiguration(
        require dirname(__DIR__, 4).'/config/wheel.php'
    );
}

/**
 * Returns a fixed sequence of symbol indexes into [star, diamond, circle].
 */
class ScriptedSymbolRng implements RngInterface
{
    private int $cursor = 0;

    /**
     * @param  list<int>  $indexes
     */
    public function __construct(private array $indexes) {}

    public function nextInt(int $min, int $max): int
    {
        $value = $this->indexes[$this->cursor] ?? 0;
        $this->cursor++;

        return max($min, min($max, $value));
    }

    public function nextFloat(): float
    {
        return 0.0;
    }
}

test('bonus sequence ends at first triple and pays final cents only', function () {
    // star, diamond, star, circle, star → star wins at length 5
    $rng = new ScriptedSymbolRng([0, 1, 0, 2, 0]);
    $generator = new BonusSequenceGenerator(bonusConfig(), $rng);

    $bonus = $generator->generate(200);

    expect($bonus['sequence'])->toBe(['star', 'diamond', 'star', 'circle', 'star'])
        ->and($bonus['winningCollection'])->toBe('star')
        ->and($bonus['baseWinCents'])->toBe(400)
        ->and($bonus['multiplier'])->toBe(2)
        ->and($bonus['finalWinCents'])->toBe(800)
        ->and(count($bonus['sequence']))->toBeGreaterThanOrEqual(3)
        ->and(count($bonus['sequence']))->toBeLessThanOrEqual(7);
});

test('bonus sequence length is always between 3 and 7', function () {
    $config = bonusConfig();

    for ($seed = 1; $seed <= 200; $seed++) {
        $bonus = (new BonusSequenceGenerator($config, new SeedableRng($seed)))->generate(100);

        $length = count($bonus['sequence']);
        expect($length)->toBeGreaterThanOrEqual(3)
            ->and($length)->toBeLessThanOrEqual(7);

        $counts = array_count_values($bonus['sequence']);
        expect($counts[$bonus['winningCollection']])->toBe(3);

        foreach ($counts as $symbol => $count) {
            if ($symbol !== $bonus['winningCollection']) {
                expect($count)->toBeLessThan(3);
            }
        }

        $expectedFinal = $bonus['baseWinCents'] * $bonus['multiplier'];
        expect($bonus['finalWinCents'])->toBe($expectedFinal)
            ->and($bonus['baseWinCents'])->toBe(200);
    }
});

test('bonus generator is deterministic for a fixed seed', function () {
    $config = bonusConfig();

    expect((new BonusSequenceGenerator($config, new SeedableRng(11)))->generate(50))
        ->toBe((new BonusSequenceGenerator($config, new SeedableRng(11)))->generate(50));
});
