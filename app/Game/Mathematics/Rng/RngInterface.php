<?php

namespace App\Game\Mathematics\Rng;

interface RngInterface {
    public function nextInt(int $min, int $max): int;

    public function nextFloat(): float;
}
