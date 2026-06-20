<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;
use Illuminate\Support\Facades\Auth;

return SettingForm::tab(
    identifier: 'attribute',
    label: __( 'General' ),
    fields: SettingForm::fields(
        FormInput::select(
            label: __( 'Theme' ),
            name: 'theme',
            value: Auth::user()->attribute->theme ?? '',
            options: Helper::kvToJsOptions( config( 'nexopos.themes' ) ),
            description: __( 'Define what is the theme that applies to the dashboard.' ),
        ),
        FormInput::media(
            label: __( 'Avatar' ),
            name: 'avatar_link',
            value: Auth::user()->attribute->avatar_link ?? '',
            data: [
                'user_id' => Auth::id(),
                'type' => 'url',
            ],
            description: __( 'Define the image that should be used as an avatar.' ),
        ),
        FormInput::select(
            label: __( 'Language' ),
            name: 'language',
            value: Auth::user()->attribute->language ?? '',
            options: Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
            description: __( 'Choose the language for the current account.' ),
        ),
    )
);

return [
    'label' => __( 'General' ),
    'fields' => [
        [
            'label' => __( 'Theme' ),
            'name' => 'theme',
            'value' => Auth::user()->attribute->theme ?? '',
            'type' => 'select',
            'options' => Helper::kvToJsOptions( config( 'nexopos.themes' ) ),
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
