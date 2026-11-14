<?php

namespace App\Game\Mathematics;

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\Rng\RngInterface;
use InvalidArgumentException;
use RuntimeException;

readonly class BonusSequenceGenerator
{
    private const WINNING_COUNT = 3;

    public function __construct(
        private GameConfiguration $config,
        private RngInterface $rng,
    ) {}

    /**
     * @return array{
     *     sequence: list<string>,
     *     winningCollection: string,
     *     baseWinCents: int,
     *     multiplier: int,
     *     finalWinCents: int
     * }
     */
    public function generate(int $betCents): array
    {
        if ($betCents <= 0) {
            throw new InvalidArgumentException('betCents must be positive.');
        }

        $symbols = $this->config->bonusSymbolIds();

        if (count($symbols) < 2) {
            throw new RuntimeException('BONUS requires at least two collection symbols.');
        }

        $counts = array_fill_keys($symbols, 0);
        $sequence = [];
        $winner = null;

        while ($winner === null) {
            $symbol = $symbols[$this->rng->nextInt(0, count($symbols) - 1)];
            $sequence[] = $symbol;
            $counts[$symbol]++;

            if ($counts[$symbol] === self::WINNING_COUNT) {
                $winner = $symbol;
            }

            if (count($sequence) > 7) {
                throw new RuntimeException('BONUS sequence exceeded maximum length of 7.');
            }
        }

        $multiplier = $this->config->bonusCollectionMultipliers()[$winner];
        $baseWinCents = $betCents * $this->config->bonusBaseMultiplier();
        $finalWinCents = $baseWinCents * $multiplier;

        return [
            'sequence' => $sequence,
            'winningCollection' => $winner,
            'baseWinCents' => $baseWinCents,
            'multiplier' => $multiplier,
            'finalWinCents' => $finalWinCents,
        ];
    }
}
