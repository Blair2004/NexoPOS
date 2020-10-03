<?php
return [
    'version'       =>  '4.0',
    'db_version'    =>  '1.0',
    'pos'           =>  [
        'payments'  =>  [
            [
                'label'         =>  'Cash',
                'identifier'    =>  'cash-payment'
            ], [
                'label'         =>  'Bank Transfer',
                'identifier'    =>  'bank-payment'
            ]
        ]
    ]
];