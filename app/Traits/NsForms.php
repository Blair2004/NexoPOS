<?php
namespace App\Traits;

use App\Classes\Hook;
use App\Services\CrudService;
use App\Services\SettingsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait NsForms
{
    /**
     * Provide a callback notation for
     * a specific method
     */
    public static function method( string $methodName ): string
    {
        return get_called_class() . '@' . $methodName;
    }

    /**
     * Extracts validation from either a crud form or setting page.
     */
    public function extractValidation( CrudService|SettingsPage $formObject, $model = null ): array
    {
        $form   =   $this->getFormObject( formObject: $formObject, model: $model );

        $rules = [];

        if ( isset( $form[ 'main' ][ 'validation' ] ) ) {
            $rules[ $form[ 'main' ][ 'name' ] ] = $form[ 'main' ][ 'validation' ];
        }

        foreach ( $form[ 'tabs' ] as $tabKey => $tab ) {
            if ( ! empty( $tab[ 'fields' ] ) ) {
                foreach ( $tab[ 'fields' ] as $field ) {
                    if ( isset( $field[ 'validation' ] ) ) {
                        $rules[ $tabKey ][ $field[ 'name' ] ] = $field[ 'validation' ];
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Returns the array that represent the
     * form object for either the CrudService or the SettingsPage.
     */
    private function getFormObject( CrudService|SettingsPage $formObject, $model ): array
    {
        if ( is_subclass_of( $formObject, CrudService::class ) || is_subclass_of( $formObject, SettingsPage::class ) ) {
            return Hook::filter( get_class( $formObject )::method( 'getForm' ), $formObject->getForm( $model ), compact( 'model' ) );
        }

        return [];
    }

    /**
     * Return flat fields for the crud form provided
     */
    public function getFlatForm( CrudService|SettingsPage $crud, $fields, $model = null ): array
    {
        $form   =   $this->getFormObject( formObject: $crud, model: $model );
        $data   = [];

        if ( isset( $form[ 'main' ][ 'name' ] ) ) {
            $data[ $form[ 'main' ][ 'name' ] ] = $fields[ $form[ 'main' ][ 'name' ] ];
        }

        foreach ( $form[ 'tabs' ] as $tabKey => $tab ) {
            /**
             * if the object bein used is not an instance
             * of a Crud and include the method, let's skip
             * this.
             */
            $keys = [];
            if ( method_exists( $crud, 'getTabsRelations' ) ) {
                $keys = array_keys( $crud->getTabsRelations() );
            }

            /**
             * We're ignoring the tabs
             * that are linked to a model.
             */
            if ( ! in_array( $tabKey, $keys ) && ! empty( $tab[ 'fields' ] ) ) {
                foreach ( $tab[ 'fields' ] as $field ) {
                    $value = data_get( $fields, $tabKey . '.' . $field[ 'name' ] );

                    /**
                     * if the field doesn't have any value
                     * we'll omit it. To avoid filling wrong value
                     */
                    if ( ! empty( $value ) || (int) $value === 0 ) {
                        $data[ $field[ 'name' ] ] = $value;
                    }
                }
            }
        }

        /**
         * We'll add custom fields
         * that might be added by modules
         */
        $fieldsToIgnore = array_keys( collect( $form[ 'tabs' ] )->toArray() );

        foreach ( $fields as $field => $value ) {
            if ( ! in_array( $field, $fieldsToIgnore ) ) {
                $data[ $field ] = $value;
            }
        }

        return $data;
    }

    /**
     * Return plain data that can be used
     * for inserting. The data is parsed from the defined
     * form on the Request.
     */
    public function getPlainData( $crud, Request $request, $model = null ): array
    {
        $fields = $request->post();

        return $this->getFlatForm( $crud, $fields, $model );
    }
}