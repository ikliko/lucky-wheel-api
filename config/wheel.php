<?php

return [
    'version' => '1.0.0',
    'base_spin_count' => 10,
    'free_spins_award' => 3,
    'free_spins_retrigger_limit' => 1,
    'special_a_id' => 'sector_1',
    'special_b_id' => 'sector_4',
    'special_counts' => [
        'A' => 3,
        'B' => 2,
    ],
    'target_rtp' => 0.96,
    'bonus_base_multiplier' => 2,
    'bonus_collection_multipliers' => [
        'star' => 2,
        'diamond' => 3,
        'circle' => 5,
    ],
    'initial_balance_cents' => 100000,
    'bet_options_cents' => [
        10, 20, 30, 40, 50, 60, 70, 80, 90, 100,
        150, 200, 250, 300, 350, 400, 450, 500,
        550, 600, 650, 700, 750, 800, 850, 900, 950, 1000,
    ],
    'quick_bets_cents' => [10, 50, 100, 200, 500, 1000],

    'sectors' => [
        ['id' => 'sector_0',  'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_1',  'type' => 'lose',       'multiplier' => 0], // A
        ['id' => 'sector_2',  'type' => 'win',        'multiplier' => 0.5],
        ['id' => 'sector_3',  'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_4',  'type' => 'lose',       'multiplier' => 0], // B
        ['id' => 'sector_5',  'type' => 'win',        'multiplier' => 1],
        ['id' => 'sector_6',  'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_7',  'type' => 'free_spins', 'multiplier' => 0, 'awarded_spins' => 3],
        ['id' => 'sector_8',  'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_9',  'type' => 'win',        'multiplier' => 2],
        ['id' => 'sector_10', 'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_11', 'type' => 'bonus',      'multiplier' => 0],
        ['id' => 'sector_12', 'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_13', 'type' => 'win',        'multiplier' => 5],
        ['id' => 'sector_14', 'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_15', 'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_16', 'type' => 'lose',       'multiplier' => 0],
        ['id' => 'sector_17', 'type' => 'lose',       'multiplier' => 0],
    ],

    'ordinary_lose_ids' => [
        'sector_0',
        'sector_3',
        'sector_6',
        'sector_8',
        'sector_10',
        'sector_12',
        'sector_14',
        'sector_15',
        'sector_16',
        'sector_17',
    ],

    'x_weights' => [
        'lose_bucket' => 0.495,
        'sector_2' => 0.10,
        'sector_5' => 0.05,
        'sector_9' => 0.05,
        'sector_13' => 0.076,
        'sector_7' => 0.05,
        'sector_11' => 0.179,
    ],
];
