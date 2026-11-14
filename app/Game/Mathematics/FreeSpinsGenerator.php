<?php

namespace App\Game\Mathematics;

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\Rng\RngInterface;
use InvalidArgumentException;

readonly class FreeSpinsGenerator
{
    public function __construct(
        private GameConfiguration $config,
        private RngInterface $rng,
        private BonusSequenceGenerator $bonusGenerator,
    ) {}

    /**
     * @return array{
     *     results: list<array<string, mixed>>,
     *     totalWinCents: int,
     *     retriggered: bool
     * }
     */
    public function generate(int $betCents): array
    {
        if ($betCents <= 0) {
            throw new InvalidArgumentException('betCents must be positive.');
        }

        $remaining = $this->config->freeSpinsAward();
        $retriggerLimit = $this->config->freeSpinsRetriggerLimit();
        $retriggersUsed = 0;
        $results = [];
        $totalWinCents = 0;

        while ($remaining > 0) {
            $remaining--;

            $sectorId = $this->pickUniformSectorId();
            $sector = $this->config->sectorById($sectorId);
            $type = (string) $sector['type'];
            $sectorIndex = $this->config->sectorIndexById($sectorId);

            $awardedAdditionalSpins = 0;
            $bonus = null;
            $winCents = 0;

            if ($type === 'free_spins' && $retriggersUsed < $retriggerLimit) {
                $awardedAdditionalSpins = $this->config->freeSpinsAward();
                $remaining += $awardedAdditionalSpins;
                $retriggersUsed++;
            } elseif ($type === 'bonus') {
                $bonus = $this->bonusGenerator->generate($betCents);
                $winCents = $bonus['finalWinCents'];
            } elseif ($type === 'win') {
                $winCents = $this->cashWinCents($betCents, (float) $sector['multiplier']);
            }

            $entry = [
                'sectorId' => $sectorId,
                'sectorIndex' => $sectorIndex,
                'type' => $type,
                'awardedAdditionalSpins' => $awardedAdditionalSpins,
                'winCents' => $winCents,
            ];

            if ($bonus !== null) {
                $entry['bonus'] = $bonus;
            }

            $results[] = $entry;
            $totalWinCents += $winCents;
        }

        return [
            'results' => $results,
            'totalWinCents' => $totalWinCents,
            'retriggered' => $retriggersUsed > 0,
        ];
    }

    private function pickUniformSectorId(): string
    {
        $ids = $this->config->sectorIds();

        return $ids[$this->rng->nextInt(0, count($ids) - 1)];
    }

    private function cashWinCents(int $betCents, float $multiplier): int
    {
        return (int) round($betCents * $multiplier);
    }
}
