<?php

use App\Services\Helper;
use Illuminate\Support\Facades\Auth;

return [
    'label' => __( 'General' ),
    'fields' => [
        [
            'label' => __( 'Theme' ),
            'name' => 'theme',
            'value' => Auth::user()->attribute->theme ?? '',
            'type' => 'select',
            'options' => Helper::kvToJsOptions( [
                'dark' => __( 'Dark' ),
                'light' => __( 'Light' ),
            ] ),
            'description' => __( 'Define what is the theme that applies to the dashboard.' ),
        ], [
            'label' => __( 'Avatar' ),
            'name' => 'avatar_link',
            'value' => Auth::user()->attribute->avatar_link ?? '',
            'type' => 'media',
            'data' => [
                'user_id' => Auth::id(),
                'type' => 'url',
            ],
            'description' => __( 'Define the image that should be used as an avatar.' ),
        ], [
            'label' => __( 'Language' ),
            'name' => 'language',
            'value' => Auth::user()->attribute->language ?? '',
            'type' => 'select',
            'options' => Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
            'description' => __( 'Choose the language for the current account.' ),
        ],
    ],
];
