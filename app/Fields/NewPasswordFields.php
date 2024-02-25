<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class NewPasswordFields extends FieldsService
{
    protected static $identifier = 'ns.new-password';

    public function get()
    {
        $fields = Hook::filter( 'ns-new-password-fields', [
            [
                'label' => __( 'New Password' ),
                'description' => __( 'define your new password.' ),
                'validation' => 'required|min:6',
                'name' => 'password',
                'type' => 'password',
            ],  [
                'label' => __( 'Confirm Password' ),
                'description' => __( 'confirm your new password.' ),
                'validation' => 'same:password',
                'name' => 'password_confirm',
                'type' => 'password',
            ],
        ] );

        return $fields;
    }
}
