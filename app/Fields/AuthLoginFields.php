<?php
namespace App\Fields;

use App\Services\FieldsService;

class AuthLoginFields extends FieldsService
{
    public function get()
    {
        $fields     =   [
            [
                'label'         =>  __( 'Username' ),
                'description'   =>  __( 'Provide your username.' ),
                'validation'    =>  'required',
                'type'          =>  'text',
            ], [
                'label'         =>  __( 'Password' ),
                'description'   =>  __( 'Provide your password.' ),
                'validation'    =>  'required',
                'type'          =>  'password',
            ]
        ];
        
        return $fields;
    }
}