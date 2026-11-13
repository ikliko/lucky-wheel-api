<?php

namespace App\Game\Mathematics;

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\Rng\RngInterface;
use RuntimeException;

class SpinPlanGenerator {
    /**
     * Six legal masks: indexes of the five non-adjacent special (A/B) slots.
     *
     * @var list<list<int>>
     */
    private const MASKS = [
        [0, 2, 4, 6, 8],
        [0, 2, 4, 6, 9],
        [0, 2, 4, 7, 9],
        [0, 2, 5, 7, 9],
        [0, 3, 5, 7, 9],
        [1, 3, 5, 7, 9],
    ];

    public function __construct(
        private readonly GameConfiguration       $config,
        private readonly WeightedOutcomeSelector $selector,
        private readonly RngInterface            $rng,
    ) {
    }

    /**
     * @return list<string> Exactly 10 sector ids.
     */
    public function generate(?string $previousLastSectorId = null): array {
        if ($previousLastSectorId !== null) {
            $this->assertKnownSector($previousLastSectorId);
        }

        $mask = $this->pickMask($previousLastSectorId);
        $specialIds = $this->shuffledSpecialIds();

        $sequence = array_fill(0, $this->config->baseSpinCount(), null);
        $specialCursor = 0;

        foreach ($mask as $index) {
            $sequence[$index] = $specialIds[$specialCursor++];
        }

        for ($i = 0; $i < count($sequence); $i++) {
            if ($sequence[$i] === null) {
                $sequence[$i] = $this->selector->select();
            }
        }

        /** @var list<string> $sequence */
        $this->assertPlanInvariants($sequence, $previousLastSectorId);

        return $sequence;
    }

    /**
     * @return list<int>
     */
    private function pickMask(?string $previousLastSectorId): array {
        $allowed = [];

        foreach (self::MASKS as $mask) {
            if ($this->maskAllowed($mask, $previousLastSectorId)) {
                $allowed[] = $mask;
            }
        }

        if ($allowed === []) {
            throw new RuntimeException('No legal spin-plan mask for the previous boundary sector.');
        }

        return $allowed[$this->rng->nextInt(0, count($allowed) - 1)];
    }

    /**
     * @param list<int> $mask
     */
    private function maskAllowed(array $mask, ?string $previousLastSectorId): bool {
        if ($previousLastSectorId === null) {
            return true;
        }

        if (!$this->isSpecialSector($previousLastSectorId)) {
            return true;
        }

        return !in_array(0, $mask, true);
    }

    /**
     * @return list<string>
     */
    private function shuffledSpecialIds(): array {
        $ids = [
            $this->config->specialAId(),
            $this->config->specialAId(),
            $this->config->specialAId(),
            $this->config->specialBId(),
            $this->config->specialBId(),
        ];

        // Fisher–Yates
        for ($i = count($ids) - 1; $i > 0; $i--) {
            $j = $this->rng->nextInt(0, $i);
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
        }

        return $ids;
    }

    private function isSpecialSector(string $sectorId): bool {
        return $sectorId === $this->config->specialAId()
            || $sectorId === $this->config->specialBId();
    }

    private function assertKnownSector(string $sectorId): void {
        $this->config->sectorIndexById($sectorId);
    }

    /**
     * @param list<string> $sequence
     */
    private function assertPlanInvariants(array $sequence, ?string $previousLastSectorId): void {
        if (count($sequence) !== 10) {
            throw new RuntimeException('Spin plan must contain exactly 10 sectors.');
        }

        $a = $this->config->specialAId();
        $b = $this->config->specialBId();

        $countA = count(array_filter($sequence, fn(string $id): bool => $id === $a));
        $countB = count(array_filter($sequence, fn(string $id): bool => $id === $b));

        if ($countA !== 3 || $countB !== 2) {
            throw new RuntimeException('Spin plan must contain exactly 3 A and 2 B sectors.');
        }

        for ($i = 0; $i < 9; $i++) {
            if ($this->isSpecialSector($sequence[$i]) && $this->isSpecialSector($sequence[$i + 1])) {
                throw new RuntimeException('Spin plan must not contain adjacent A/B sectors.');
            }
        }

        if ($previousLastSectorId !== null
            && $this->isSpecialSector($previousLastSectorId)
            && $this->isSpecialSector($sequence[0])) {
            throw new RuntimeException('Spin plan boundary violates A/B adjacency rule.');
        }
    }
}
