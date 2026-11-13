<?php

namespace App\Game\Mathematics\Rng;

use InvalidArgumentException;

/**
 * Deterministic RNG for tests. Not cryptographically secure.
 */
class SeedableRng implements RngInterface {
    private int $state;

    public function __construct(int $seed = 1) {
        $this->state = $seed & 0x7FFFFFFF;

        if ($this->state === 0) {
            $this->state = 1;
        }
    }

    public function nextInt(int $min, int $max): int {
        if ($min > $max) {
            throw new InvalidArgumentException('min must be <= max.');
        }

        $span = $max - $min + 1;

        return $min + (int)floor($this->nextFloat() * $span);
    }

    public function nextFloat(): float {
        // Numerical Recipes LCG
        $this->state = (int)((1664525 * $this->state + 1013904223) % 2147483648);

        return $this->state / 2147483648;
    }
}
