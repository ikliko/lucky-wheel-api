<?php

namespace App\Game\Mathematics;

use App\Game\Config\GameConfiguration;
use App\Game\Mathematics\Rng\RngInterface;
use RuntimeException;

readonly class WeightedOutcomeSelector {
    public function __construct(
        private GameConfiguration $config,
        private RngInterface      $rng,
    ) {
    }

    public function select(): string {
        $roll = $this->rng->nextFloat();
        $cumulative = 0.0;

        foreach ($this->config->xWeights() as $key => $weight) {
            $cumulative += $weight;

            if ($roll < $cumulative) {
                if ($key === 'lose_bucket') {
                    return $this->pickOrdinaryLose();
                }

                return $key;
            }
        }

        // Floating-point edge: land on last weight bucket.
        $keys = array_keys($this->config->xWeights());
        $last = $keys[array_key_last($keys)];

        if ($last === 'lose_bucket') {
            return $this->pickOrdinaryLose();
        }

        return $last;
    }

    private function pickOrdinaryLose(): string {
        $ids = $this->config->ordinaryLoseIds();

        if ($ids === []) {
            throw new RuntimeException('ordinary_lose_ids must not be empty.');
        }

        return $ids[$this->rng->nextInt(0, count($ids) - 1)];
    }
}
