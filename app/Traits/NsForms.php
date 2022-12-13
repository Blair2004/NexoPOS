<?php
namespace App\Traits;

use App\Classes\Hook;
use App\Services\CrudService;
use App\Services\SettingsPage;

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
        if ( is_subclass_of( $formObject, CrudService::class ) || is_subclass_of( $formObject, SettingsPage::class ) ) {
            $form = Hook::filter( get_class( $formObject )::method( 'getForm' ), $formObject->getForm( $model ), compact( 'model' ) );
        }

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
}