<?php
return [
    'accounts'  =>  [
        'assets'    =>  [
            'increase' => 'debit',
            'decrease' => 'credit',
            'label'     =>  fn() => __( 'Assets' )
        ],
        'liabilities'   =>  [
            'increase' => 'credit',
            'decrease' => 'debit',
            'label'     =>  fn() => __( 'Liabilities' )
        ],
        'equity'    =>  [
            'increase' => 'credit',
            'decrease' => 'debit',
            'label'     =>  fn() => __( 'Equity' )
        ],
        'revenues'  =>  [
            'increase' => 'credit',
            'decrease' => 'debit',
            'label'     =>  fn() => __( 'Revenues' )
        ],
        'expenses'  =>  [
            'increase' => 'debit',
            'decrease' => 'credit',
            'label'     =>  fn() => __( 'Expenses' )
        ]
    ]
];