<?php

namespace App\Exceptions;

use App\Services\Helper;
use Illuminate\Validation\ValidationException as MainValidationException;

class ValidationException extends MainValidationException
{
    public $validator;

    public function __construct( $validator = null )
    {
        $this->validator = $validator ?: __( 'An error occurred while validating the form.' );
    }

    public function render( $request )
    {
        if ( ! $request->expectsJson() ) {
            return response()->view( 'pages.errors.not-allowed', [
                'title' => __( 'An error has occurred' ),
                'message' => __( 'Unable to proceed, the submitted form is not valid.' ),
                'back' => Helper::getValidPreviousUrl( $request ),
            ] );
        }

        return response()->json( [
            'status' => 'error',
            'message' => __( 'Unable to proceed the form is not valid' ),
            'data' => [
                'errors' => $this->toHumanError(),
            ],
        ], 422 );
    }

    /**
     * We'll return human understandable errors
     *
     * @return array $errors
     */
    private function toHumanError()
    {
        $errors = [];

        if ( $this->validator ) {
            $errors = $this->errors();

            $errors = collect( $errors )->map( function ( $messages ) {
                return collect( $messages )->map( function ( $message ) {
                    switch ( $message ) {
                        case 'validation.unique' :  return __( 'This value is already in use on the database.' );
                        case 'validation.required' :  return __( 'This field is required.' );
                        case 'validation.array' :  return __( 'This field does\'nt have a valid value.' );
                        case 'validation.accepted' :  return __( 'This field should be checked.' );
                        case 'validation.active_url' :  return __( 'This field must be a valid URL.' );
                        case 'validation.email' :  return __( 'This field is not a valid email.' );
                        default: return $message;
                    }
                } );
            } );
        }

        return $errors;
    }
}
