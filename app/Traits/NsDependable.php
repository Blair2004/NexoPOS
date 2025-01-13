<?php

namespace App\Traits;

use App\Classes\Hook;
use App\Exceptions\NotAllowedException;

trait NsDependable
{
    /**
     * We'll provide a custom method to ensure
     * we can safely check for dependencies.
     */
    public function delete()
    {
        /**
         * Let's verify if the current model
         * is a dependency for other models.
         */
        if ( method_exists( $this, 'setDependencies' ) ) {
            $declaredDependencies = Hook::filter( self::class . '@' . 'setDependencies', $this->setDependencies() );
        } else {
            $declaredDependencies = [];
        }

        foreach ( $declaredDependencies as $class => $indexes ) {
            $localIndex = $indexes['local_index'] ?? 'id';
            $request = $class::where( $indexes['foreign_index'], $this->$localIndex );
            $dependencyFound = $request->first();
            $countDependency = $request->count() - 1;

            if ( $dependencyFound instanceof $class ) {
                if ( isset( $this->{$indexes['local_name']} ) ) {
                    if ( ! empty( $indexes['foreign_name'] ) ) {
                        $foreignName = $dependencyFound->{$indexes['foreign_name']} ?? __( 'Undefined Item' );

                        /**
                         * The local name will always pull from
                         * the related model table.
                         */
                        $localName = $this->{$indexes['local_name']};
                    } elseif ( ! empty( $indexes[ 'related' ] ) && is_array( $indexes[ 'related' ] ) ) {
                        /**
                         * if the foreign name is an array
                         * we'll pull the first model set as linked
                         * to the item being deleted.
                         */
                        $relatedSubModel = $indexes['related'][ 'model' ]; // model name
                        $localIndex = $indexes['related'][ 'local_index' ]; // local index on the dependency table $dependencyFound
                        $foreignIndex = $indexes['related'][ 'foreign_index' ] ?? 'id'; // foreign index on the related table $model
                        $labelColumn = $indexes['related'][ 'foreign_name' ] ?? 'name'; // foreign index on the related table $model
                        $prefix = $indexes['related'][ 'prefix' ] ?? null;
                        $localName = $this->{$indexes['local_name']};

                        /**
                         * we'll find if we find the model
                         * for the provided details.
                         */
                        $result = $relatedSubModel::where( $localIndex, $dependencyFound->$foreignIndex )->first();

                        /**
                         * the model might exists. If that doesn't exists
                         * then probably it's not existing. There might be a misconfiguration
                         * on the relation.
                         */
                        if ( $result instanceof $relatedSubModel ) {
                            if ( is_callable( $prefix ) ) {
                                $foreignName = $prefix( $result->$labelColumn );
                            } else {
                                $foreignName = $result->$labelColumn ?? __( 'Undefined Item' );
                            }
                        } else {
                            $foreignName = $result->$labelColumn ?? __( 'Non-existent Item' );
                        }
                    }

                    throw new NotAllowedException( sprintf(
                        __( 'Unable to delete "%s" as it\'s a dependency for "%s"%s' ),
                        $localName,
                        $foreignName,
                        $countDependency >= 1 ? ' ' . trans_choice( '{1} and :count more item.|[2,*] and :count more items.', $countDependency, ['count' => $countDependency] ) : '.'
                    ) );

                } else {
                    throw new NotAllowedException( sprintf(
                        (
                            $countDependency === 1 ?
                            __( 'Unable to delete this resource as it has %s dependency with %s item.' ) :
                            __( 'Unable to delete this resource as it has %s dependencies with %s items.' )
                        ),
                        $class,
                        $countDependency
                    ) );
                }
            }
        }

        /**
         * If everything went go from here, we can
         * safely delete the model
         */
        return parent::delete();
    }
}
