<?php
namespace App\Fields;

use Illuminate\Validation\Rule;
use App\Models\Provider;

class ProviderFields
{
    public function get( Provider $model = null )
    {
        $name                   =   new \stdClass;
        $name->name             =   'name';
        $name->description      =   __( 'Mention the provider name.' );
        $name->label            =   __( 'Provider Name' );
        $name->validation       =   'required|min:4';
        $name->type             =   'text';

        $email                  =   new \stdClass;
        $email->name            =   'email';
        $email->type            =   'email';
        $email->validation      =   [ 'required', 'email', Rule::unique( 'nexopos_providers' )->ignore( @$model->id, 'id' )];
        $email->label           =   __( 'Email' );
        $email->description     =   __( 'It could be used to send some informations to the provider.' );

        $surname                  =   new \stdClass;
        $surname->name            =   'surname';
        $surname->type            =   'text';
        $surname->label           =   __( 'Surname' );
        $surname->description     =   __( 'If the provider has any surname, provide it here.' );

        $phone                  =   new \stdClass;
        $phone->name            =   'phone';
        $phone->type            =   'text';
        $phone->label           =   __( 'Phone' );
        $phone->description     =   __( 'Mention the phone number of the provider.' );

        $address_1                  =   new \stdClass;
        $address_1->name            =   'address_1';
        $address_1->type            =   'text';
        $address_1->label           =   __( 'Address 1' );
        $address_1->description     =   __( 'Mention the first address of the provider.' );

        $address_2                  =   new \stdClass;
        $address_2->name            =   'address_2';
        $address_2->type            =   'text';
        $address_2->label           =   __( 'Address 2' );
        $address_2->description     =   __( 'Mention the second address of the provider.' );

        $description                  =   new \stdClass;
        $description->name            =   'description';
        $description->type            =   'text';
        $description->label           =   __( 'Description' );
        $description->description     =   __( 'Mention any description of the provider.' );

        return collect([
            $name, 
            $surname, 
            $email,
            $phone,
            $address_1, 
            $address_2,
            $description
        ])->map( function( $field ) use ( $model ) {
            if ( $model instanceof Provider ) {
                $field->value   =   $model->{$field->name} ?: '';
            } 
            return $field;
        })->toArray();
    }
}