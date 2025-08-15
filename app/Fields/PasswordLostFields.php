<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class PasswordLostFields extends FieldsService
{
    /**
     * The unique identifier of the form
     **/
    const IDENTIFIER = 'ns.password-lost';

    /**
     * Will ensure the fields are automatically
     * loaded
     **/
    const AUTOLOAD = true;

    public function get()
    {
        $fields = Hook::filter( 'ns-password-lost-fields', [
            [
                'label' => __( 'Email' ),
                'description' => __( 'Provide your email.' ),
                'validation' => 'required',
                'name' => 'email',
                'type' => 'text',
            ],
        ] );

        return $fields;
    }
}
