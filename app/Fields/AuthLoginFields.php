<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class AuthLoginFields extends FieldsService
{
    /**
     * The unique identifier of the form
    **/
    const IDENTIFIER = 'ns.login';

    /**
     * Will ensure the fields are automatically 
     * loaded
    **/
    const AUTOLOAD = true;

    public function get()
    {
        $fields = Hook::filter( 'ns-login-fields', [
            [
                'label' => __( 'Username' ),
                'description' => __( 'Provide your username.' ),
                'validation' => 'required',
                'name' => 'username',
                'type' => 'text',
            ], [
                'label' => __( 'Password' ),
                'description' => __( 'Provide your password.' ),
                'validation' => 'required',
                'name' => 'password',
                'type' => 'password',
            ],
        ] );

        return $fields;
    }
}
