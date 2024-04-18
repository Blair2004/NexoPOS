<?php

namespace App\Services;

use App\Classes\Hook;
use App\Events\SettingsSavedEvent;
use App\Traits\NsForms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SettingsPage
{
    use NsForms;

    protected $form = [];

    protected string $view;

    /**
     * returns the defined form
     */
    public function getForm(): array
    {
        return collect( $this->form )->mapWithKeys( function ( $tab, $key ) {
            if ( $tab === 'tabs' ) {
                return [
                    $key => collect( $tab )->mapWithKeys( function ( $tab, $key ) {
                        /**
                         * in case not fields is provided
                         * let's save the tab with no fields.
                         */
                        if ( ! isset( $tab[ 'fields' ] ) ) {
                            $tab[ 'fields' ] = [];
                        }

                        return [ $key => $tab ];
                    } ),
                ];
            }

            return [ $key => $tab ];
        } )->toArray();
    }

    public function getIdentifier()
    {
        return get_called_class()::IDENTIFIER;
    }

    /**
     * In case the form is used as a resource,
     * "index" is used as a main method.
     */
    public static function index()
    {
        return self::renderForm();
    }

    public static function renderForm()
    {
        $className = get_called_class();
        $settings = new $className;

        /**
         * if something has to be made before a form
         * is renderer, we'll trigger the method here if
         * that exists.
         */
        if ( method_exists( $settings, 'beforeRenderForm' ) ) {
            $settings->beforeRenderForm();
        }

        /**
         * When the settingsPage class has the "getView" method,
         * we return it as it might provide a custom View page.
         */
        if ( method_exists( $settings, 'getView' ) ) {
            return $settings->getView();
        }

        $form = $settings->getForm();

        /**
         * if the form is an instance of a view
         * that view is rendered in place of the default form.
         */
        return View::make( 'pages.dashboard.settings.form', [
            'title' => $form[ 'title' ] ?? __( 'Untitled Settings Page' ),

            /**
             * retrive the description provided on the SettingsPage instance.
             * Otherwhise a default settings is used .
             */
            'description' => $form[ 'description' ] ?? __( 'No description provided for this settings page.' ),

            /**
             * retrieve the identifier of the settings if it's defined.
             * this is used to load the settings asynchronously.
             */
            'identifier' => $settings->getIdentifier(),
        ] );
    }

    /**
     * Validate a form using a provided
     * request. Based on the actual settings page rules
     *
     * @return array
     */
    public function validateForm( Request $request )
    {
        $arrayRules = $this->extractValidation();

        /**
         * As rules might contains complex array (with Rule class),
         * we don't want that array to be transformed using the dot key form.
         */
        $isolatedRules = $this->isolateArrayRules( $arrayRules );

        /**
         * Let's properly flat everything.
         */
        $flatRules = collect( $isolatedRules )->mapWithKeys( function ( $rule ) {
            return [ $rule[0] => $rule[1] ];
        } )->toArray();

        return $flatRules;
    }

    /**
     * Proceed to a saving using te provided
     * request along with the plain data
     *
     * @return array
     */
    public function saveForm( Request $request )
    {
        /**
         * @var Options
         */
        $options = app()->make( Options::class );
        $data = [];
        $inputs = Hook::filter( SettingsPage::method( 'saveForm' ), $this->getPlainData( $request ) );

        foreach ( $inputs as $key => $value ) {
            if ( $value === null ) {
                $options->delete( $key );
            } else {
                $options->set( $key, $value );
                $data[ $key ] = $value;
            }
        }

        event( new SettingsSavedEvent(
            data: $data,
            settingsClass: get_class( $this )
        ) );

        return [
            'status' => 'success',
            'message' => __( 'The form has been successfully saved.' ),
        ];
    }
}
