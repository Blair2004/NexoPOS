<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class AuthLoginFields extends FieldsService
{
    protected static $identifier = 'ns.login';

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
