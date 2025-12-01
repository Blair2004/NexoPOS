<?php

namespace App\Fields;

use App\Classes\Form;
use App\Classes\FormInput;
use App\Classes\Hook;
use App\Services\FieldsService;

class AuthLoginFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.login';

    /**
     * Will ensure the fields are automatically loaded
     **/
    const AUTOLOAD = true;

    public function get()
    {
        $fields = Hook::filter( 'ns-login-fields', 
            Form::fields(
                FormInput::text(
                    label: __( 'Username' ),
                    description: __( 'Provide your username.' ),
                    validation: 'required|min:5',
                    name: 'username',
                ),
                FormInput::password(
                    label: __( 'Password' ),
                    description: __( 'Provide your password.' ),
                    validation: 'required|min:6',
                    name: 'password',
                )
            )
        );

        return $fields;
    }
}
