<?php

namespace App\Game\Config;

use InvalidArgumentException;

class GameConfiguration
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('wheel');

        $this->assertValid();
    }

    public function version(): string
    {
        return (string) $this->config['version'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sectors(): array
    {
        return $this->config['sectors'];
    }

    public function specialAId(): string
    {
        return (string) $this->config['special_a_id'];
    }

    public function specialBId(): string
    {
        return (string) $this->config['special_b_id'];
    }

    /**
     * @return list<int>
     */
    public function betOptionsCents(): array
    {
        return $this->config['bet_options_cents'];
    }

    /**
     * @return list<int>
     */
    public function quickBetsCents(): array
    {
        return $this->config['quick_bets_cents'];
    }

    public function freeSpinsAward(): int
    {
        return (int) $this->config['free_spins_award'];
    }

    public function freeSpinsRetriggerLimit(): int
    {
        return (int) $this->config['free_spins_retrigger_limit'];
    }

    public function bonusBaseMultiplier(): int
    {
        return (int) $this->config['bonus_base_multiplier'];
    }

    /**
     * @return array<string, int>
     */
    public function bonusCollectionMultipliers(): array
    {
        return $this->config['bonus_collection_multipliers'];
    }

    public function isAllowedBet(int $cents): bool
    {
        return in_array($cents, $this->betOptionsCents(), true);
    }

    public function sectorIndexById(string $id): int
    {
        foreach ($this->sectors() as $index => $sector) {
            if ($sector['id'] === $id) {
                return $index;
            }
        }

        throw new InvalidArgumentException("Unknown sector id [{$id}].");
    }

    private function assertValid(): void
    {
        $sectors = $this->sectors();

        if (count($sectors) !== 18) {
            throw new InvalidArgumentException('Wheel config must define exactly 18 sectors.');
        }

        $ids = array_column($sectors, 'id');

        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Wheel sector ids must be unique.');
        }

        if (! in_array($this->specialAId(), $ids, true)
            || ! in_array($this->specialBId(), $ids, true)) {
            throw new InvalidArgumentException('Special A/B sector ids must exist in sectors.');
        }

        $weightSum = array_sum($this->config['x_weights'] ?? []);

        if (abs($weightSum - 1.0) > 0.000001) {
            throw new InvalidArgumentException('Wheel x_weights must sum to 1.0.');
        }
    }
}
