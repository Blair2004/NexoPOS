<?php

return [
    'accounts' => [
        'assets' => [
            'increase' => 'debit',
            'decrease' => 'credit',
            'label' => fn() => __( 'Assets' ),
            'account' => 1000,
        ],
        'liabilities' => [
            'increase' => 'credit',
            'decrease' => 'debit',
            'label' => fn() => __( 'Liabilities' ),
            'account' => 2000,
        ],
        'equity' => [
            'increase' => 'credit',
            'decrease' => 'debit',
            'label' => fn() => __( 'Equity' ),
            'account' => 3000,
        ],
        'revenues' => [
            'increase' => 'credit',
            'decrease' => 'debit',
            'label' => fn() => __( 'Revenues' ),
            'account' => 4000,
        ],
        'expenses' => [
            'increase' => 'debit',
            'decrease' => 'credit',
            'label' => fn() => __( 'Expenses' ),
            'account' => 5000,
        ],
    ],
];
