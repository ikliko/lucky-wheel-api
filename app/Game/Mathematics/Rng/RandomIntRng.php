<?php

namespace App\Game\Mathematics\Rng;

use InvalidArgumentException;
use RuntimeException;

class RandomIntRng implements RngInterface {
    public function nextInt(int $min, int $max): int {
        if ($min > $max) {
            throw new InvalidArgumentException('min must be <= max.');
        }

        try {
            return random_int($min, $max);
        } catch (\Exception $e) {
            throw new RuntimeException('Unable to generate random integer.', 0, $e);
        }
    }

    public function nextFloat(): float {
        return $this->nextInt(0, 1_000_000_000 - 1) / 1_000_000_000;
    }
}
