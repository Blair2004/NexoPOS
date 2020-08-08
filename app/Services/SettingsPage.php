<?php
namespace App\Services;

use Illuminate\Http\Request;

class SettingsPage
{
    protected $form     =   [];

    /**
     * returns the defined form
     * @return array
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Validate a form using a provided
     * request. Based on the actual settings page rules
     * @param Request $request
     * @return array
     */
    public function validateForm( Request $request )
    {
        $service            =   new CrudService;
        $arrayRules         =   $service->extractCrudValidation( $this, null );

        /**
         * As rules might contains complex array (with Rule class),
         * we don't want that array to be transformed using the dot key form.
         */
        $isolatedRules  =   $service->isolateArrayRules( $arrayRules );

        /**
         * Let's properly flat everything.
         */
        $flatRules      =   collect( $isolatedRules )->mapWithKeys( function( $rule ) {
            return [ $rule[0] => $rule[1] ];
        })->toArray();
    }
}