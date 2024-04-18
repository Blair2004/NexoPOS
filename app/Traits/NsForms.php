<?php

namespace App\Traits;

use App\Classes\Hook;
use App\Services\CrudService;
use App\Services\SettingsPage;
use Illuminate\Http\Request;

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

    public function extractProductValidaton( $model = null ): array
    {
        $form = $this->getFormObject( $model );

        $rules = [];

        if ( isset( $form['main']['validation'] ) ) {
            $rules[$form['main']['name']] = $form['main']['validation'];
        }

        foreach ( $form['variations'] as $variationKey => $variation ) {
            foreach ( $variation[ 'tabs' ] as $tabIdentifier => $tab ) {
                if ( ! empty( $tab[ 'fields' ] ) ) {
                    foreach ( $tab[ 'fields' ] as $field ) {
                        if ( isset( $field[ 'validation' ] ) ) {
                            $rules[ 'variations' ][ '*' ][ $tabIdentifier ][ $field[ 'name' ] ] = $field[ 'validation' ];
                        }

                        if ( isset( $field[ 'groups' ] ) ) {
                            foreach ( $field[ 'groups' ] as $index => $groupField ) {
                                if ( isset( $groupField[ 'fields' ] ) ) {
                                    foreach ( $groupField[ 'fields' ] as $__field ) {
                                        if ( isset( $__field[ 'validation' ] ) ) {
                                            $rules[ 'variations' ][ '*' ][ $tabIdentifier ][ $field[ 'name' ] ][ '*' ][ $__field[ 'name' ] ] = $__field[ 'validation' ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Extracts validation from either a crud form or setting page.
     */
    public function extractValidation( $model = null ): array
    {
        $form = $this->getFormObject( model: $model );

        $rules = [];

        if ( isset( $form[ 'main' ][ 'validation' ] ) ) {
            $rules[ $form[ 'main' ][ 'name' ] ] = $form[ 'main' ][ 'validation' ];
        }

        foreach ( $form[ 'tabs' ] as $tabIdentifier => $tab ) {
            if ( ! empty( $tab[ 'fields' ] ) ) {
                foreach ( $tab[ 'fields' ] as $field ) {
                    if ( isset( $field[ 'validation' ] ) ) {
                        $rules[ $tabIdentifier ][ $field[ 'name' ] ] = $field[ 'validation' ];
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
    private function getFormObject( $model = null ): array
    {
        if ( is_subclass_of( $this, CrudService::class ) || is_subclass_of( $this, SettingsPage::class ) ) {
            return Hook::filter( get_class( $this )::method( 'getForm' ), $this->getForm( $model ), compact( 'model' ) );
        }

        return [];
    }

    /**
     * Isolate Rules that use the Rule class
     */
    public function isolateArrayRules( array $arrayRules, string $parentKey = '' ): array
    {
        $rules = [];

        foreach ( $arrayRules as $key => $value ) {
            if ( is_array( $value ) && collect( array_keys( $value ) )->filter( function ( $key ) {
                return is_string( $key );
            } )->count() > 0 ) {
                $rules = array_merge( $rules, $this->isolateArrayRules( $value, $key ) );
            } else {
                $rules[] = [ ( ! empty( $parentKey ) ? $parentKey . '.' : '' ) . $key, $value ];
            }
        }

        return $rules;
    }

    /**
     * Return flat fields for the crud form provided
     */
    public function getFlatForm( array $fields, $model = null ): array
    {
        $form = $this->getFormObject( model: $model );
        $data = [];

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
            if ( method_exists( $this, 'getTabsRelations' ) ) {
                $keys = array_keys( $this->getTabsRelations() );
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
    public function getPlainData( Request $request, $model = null ): array
    {
        $fields = $request->post();

        return $this->getFlatForm( $fields, $model );
    }

    /**
     * The PHP version of FormValidation.extractForm.
     * This returns a flattened version of a Form.
     */
    public function extractForm( $form ): array
    {
        $formValue = [];

        if ( isset( $form['main'] ) ) {
            $formValue[$form['main']['name']] = $form['main']['value'];
        }

        if ( isset( $form['tabs'] ) ) {
            foreach ( $form['tabs'] as $tabIdentifier => $tab ) {
                if ( ! isset( $formValue[$tabIdentifier] ) ) {
                    $formValue[$tabIdentifier] = [];
                }

                $formValue[$tabIdentifier] = $this->extractFields( $tab[ 'fields' ] );
            }
        }

        return $formValue;
    }

    public function extractFields( $fields, $formValue = [] )
    {
        foreach ( $fields as $field ) {
            $formValue[$field['name']] = $field['value'] ?? '';
        }

        return $formValue;
    }
}
