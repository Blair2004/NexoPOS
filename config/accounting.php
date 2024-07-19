<?php
return [
    'accounts'  =>  [
        'assets'    =>  [
            'credit'    =>  'decrease',
            'debit'     =>  'increase',
            'label'     =>  fn() => __( 'Assets' )
        ],
        'liabilities'   =>  [
            'credit'    =>  'increase',
            'debit'     =>  'decrease',
            'label'     =>  fn() => __( 'Liabilities' )
        ],
        'equity'    =>  [
            'credit'    =>  'increase',
            'debit'     =>  'decrease',
            'label'     =>  fn() => __( 'Equity' )
        ],
        'revenues'  =>  [
            'credit'    =>  'increase',
            'debit'     =>  'decrease',
            'label'     =>  fn() => __( 'Revenues' )
        ],
        'expenses'  =>  [
            'credit'    =>  'increase',
            'debit'     =>  'decrease',
            'label'     =>  fn() => __( 'Expenses' )
        ]
    ]
];