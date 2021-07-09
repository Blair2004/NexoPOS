<?php
namespace App\Fields;

use App\Classes\Hook;
use App\Services\FieldsService;

class PasswordLostFields extends FieldsService
{
    public function get()
    {
        $fields     =   Hook::filter( 'ns-password-lost-fields', [
            [
                'label'         =>  __( 'Email' ),
                'description'   =>  __( 'Provide your email.' ),
                'validation'    =>  'required',
                'name'          =>  'email',
                'type'          =>  'text',
            ], 
        ]);
        
        return $fields;
    }
}