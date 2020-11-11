<?php

use Illuminate\Support\Facades\Auth;

return [
    'label'     =>      __( 'General' ),
    'fields'    =>      [
        [
            'label'         =>  __( 'First Name' ),
            'name'          =>  'first_name',
            'value'         =>  Auth::user()->attribute->first_name ?? '',
            'type'          =>  'text',
            'description'   =>  __( 'Define what is the user first name. If not provided, the username is used instead.' ),
        ], [
            'label'         =>  __( 'Second Name' ),
            'name'          =>  'second_name',
            'value'         =>  Auth::user()->attribute->second_name ?? '',
            'type'          =>  'text',
            'description'   =>  __( 'Define what is the user second name. If not provided, the username is used instead.' ),
        ],
    ]
];