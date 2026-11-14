<?php

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\BonusSequenceGenerator;
use App\Game\Mathematics\FreeSpinsGenerator;
use App\Game\Mathematics\Rng\RngInterface;
use App\Game\Mathematics\Rng\SeedableRng;

function freeSpinsConfig(): GameConfiguration
{
    return new GameConfiguration(
        require dirname(__DIR__, 4).'/config/wheel.php'
    );
}

function makeFreeSpinsGenerator(RngInterface $rng): FreeSpinsGenerator
{
    $config = freeSpinsConfig();

    return new FreeSpinsGenerator(
        $config,
        $rng,
        new BonusSequenceGenerator($config, $rng),
    );
}

test('free spins batch length is 3 or 6 with at most one retrigger', function () {
    $config = freeSpinsConfig();

    for ($seed = 1; $seed <= 300; $seed++) {
        $batch = makeFreeSpinsGenerator(new SeedableRng($seed))->generate(100);
        $count = count($batch['results']);

        expect($count)->toBeIn([3, 6])
            ->and($batch['retriggered'])->toBe($count === 6);

        $retriggers = 0;
        foreach ($batch['results'] as $result) {
            if ($result['awardedAdditionalSpins'] > 0) {
                $retriggers++;
                expect($result['type'])->toBe('free_spins')
                    ->and($result['awardedAdditionalSpins'])->toBe(3);
            }

            expect($result)->toHaveKeys([
                'sectorId',
                'sectorIndex',
                'type',
                'awardedAdditionalSpins',
                'winCents',
            ]);

            if ($result['type'] === 'bonus') {
                expect($result)->toHaveKey('bonus')
                    ->and($result['winCents'])->toBe($result['bonus']['finalWinCents']);
            }
        }

        expect($retriggers)->toBeLessThanOrEqual(1);

        $sum = array_sum(array_column($batch['results'], 'winCents'));
        expect($batch['totalWinCents'])->toBe($sum);
    }
});

test('second free spins symbol after retrigger awards zero extra spins', function () {
    $config = freeSpinsConfig();
    $ids = $config->sectorIds();
    $fsIndex = array_search('sector_7', $ids, true);
    $loseIndex = array_search('sector_0', $ids, true);

    // First pick FS (retrigger), then more FS symbols — only first awards +3.
    $script = [
        $fsIndex,
        $fsIndex,
        $fsIndex,
        $loseIndex,
        $loseIndex,
        $loseIndex,
    ];

    $rng = new class($script) implements RngInterface
    {
        private int $cursor = 0;

        public function __construct(private array $sectorIndexes) {}

        public function nextInt(int $min, int $max): int
        {
            // FreeSpinsGenerator sector pick uses 0..17; BonusSequence uses 0..2.
            if ($max === 17) {
                $value = $this->sectorIndexes[$this->cursor] ?? 0;
                $this->cursor++;

                return $value;
            }

            return 0; // always star for any nested bonus
        }

        public function nextFloat(): float
        {
            return 0.0;
        }
    };

    $batch = makeFreeSpinsGenerator($rng)->generate(100);

    expect($batch['results'])->toHaveCount(6)
        ->and($batch['retriggered'])->toBeTrue()
        ->and($batch['results'][0]['awardedAdditionalSpins'])->toBe(3)
        ->and($batch['results'][1]['awardedAdditionalSpins'])->toBe(0)
        ->and($batch['results'][2]['awardedAdditionalSpins'])->toBe(0);
});

test('free spins generator is deterministic for a fixed seed', function () {
    expect(makeFreeSpinsGenerator(new SeedableRng(21))->generate(200))
        ->toBe(makeFreeSpinsGenerator(new SeedableRng(21))->generate(200));
});
