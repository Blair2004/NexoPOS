<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;
use Illuminate\Support\Facades\Auth;

return SettingForm::tab(
    label: __( 'Personal' ),
    identifier: 'personal',
    fields: SettingForm::fields(
        FormInput::text(
            label: __( 'First Name' ),
            name: 'first_name',
            value: Auth::user()->first_name ?? '',
            description: __( 'Define the first name of the user.' ),
        ),
        FormInput::text(
            label: __( 'Last Name' ),
            name: 'last_name',
            value: Auth::user()->last_name ?? '',
            description: __( 'Define the last name of the user.' ),
        ),
        FormInput::select(
            label: __( 'Gender' ),
            name: 'gender',
            value: Auth::user()->gender ?? '',
            options: Helper::kvToJsOptions( [
                'male' => __( 'Male' ),
                'female' => __( 'Female' ),
                'other' => __( 'Other' ),
            ] ),
            description: __( 'Select the gender of the user.' ),
        ),
        FormInput::text(
            label: __( 'Phone' ),
            name: 'phone',
            value: Auth::user()->phone ?? '',
            description: __( 'Define the phone number of the user.' ),
        ),
        FormInput::text(
            label: __( 'Pobox' ),
            name: 'pobox',
            value: Auth::user()->pobox ?? '',
            description: __( 'Define the P.O. Box of the user.' ),
        )
    )
);
