<?php

use App\Services\Helper;
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
        ], [
            'label'         =>  __( 'Theme' ),
            'name'          =>  'theme',
            'value'         =>  Auth::user()->attribute->theme ?? '',
            'type'          =>  'select',
            'options'       =>  Helper::kvToJsOptions([
                'dark'      =>  __( 'Dark' ),
                'light'     =>  __( 'Light' )
            ]),
            'description'   =>  __( 'Define what is the theme that applies to the dashboard.' ),
        ], [
            'label'         =>  __( 'Avatar' ),
            'name'          =>  'avatar_link',
            'value'         =>  Auth::user()->attribute->avatar_link ?? '',
            'type'          =>  'media',
            'data'          =>  [
                'user_id'   =>  Auth::id(),
                'type'      =>  'url'
            ],
            'description'   =>  __( 'Define the image that should be used as an avatar.' ),
        ], [
            'label'         =>  __( 'Language' ),
            'name'          =>  'language',
            'value'         =>  Auth::user()->attribute->language ?? '',
            'type'          =>  'select',
            'options'       =>  Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
            'description'   =>  __( 'Choose the language for the current account.' ),
        ],
    ]
];