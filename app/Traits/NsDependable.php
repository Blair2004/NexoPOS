<?php

namespace App\Traits;

use App\Classes\Hook;
use App\Exceptions\NotAllowedException;

trait NsDependable
{
    public function getDeclaredDependencies()
    {
        return $this->isDependencyFor;
    }

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
        $declaredDependencies = Hook::filter( self::class . '@' . 'getDeclaredDependencies', $this->getDeclaredDependencies() );

        foreach ( $declaredDependencies as $class => $indexes ) {
            $localIndex = $indexes['local_index'] ?? 'id';
            $request = $class::where( $indexes['foreign_index'], $this->$localIndex );
            $dependencyFound = $request->first();
            $countDependency = $request->count() - 1;

            if ( $dependencyFound instanceof $class ) {
                if ( isset( $model->{$indexes['local_name']} ) && ! empty( $indexes['foreign_name'] ) ) {
                    /**
                     * if the foreign name is an array
                     * we'll pull the first model set as linked
                     * to the item being deleted.
                     */
                    if ( is_array( $indexes['foreign_name'] ) ) {
                        $relatedSubModel = $indexes['foreign_name'][0]; // model name
                        $localIndex = $indexes['foreign_name'][1]; // local index on the dependency table $dependencyFound
                        $foreignIndex = $indexes['foreign_name'][2] ?? 'id'; // foreign index on the related table $model
                        $labelColumn = $indexes['foreign_name'][3] ?? 'name'; // foreign index on the related table $model

                        /**
                         * we'll find if we find the model
                         * for the provided details.
                         */
                        $result = $relatedSubModel::where( $foreignIndex, $dependencyFound->$localIndex )->first();

                        /**
                         * the model might exists. If that doesn't exists
                         * then probably it's not existing. There might be a misconfiguration
                         * on the relation.
                         */
                        if ( $result instanceof $relatedSubModel ) {
                            $foreignName = $result->$labelColumn ?? __( 'Unidentified Item' );
                        } else {
                            $foreignName = $result->$labelColumn ?? __( 'Non-existent Item' );
                        }
                    } else {
                        $foreignName = $dependencyFound->{$indexes['foreign_name']} ?? __( 'Unidentified Item' );
                    }

                    /**
                     * The local name will always pull from
                     * the related model table.
                     */
                    $localName = $model->{$indexes['local_name']};

                    throw new NotAllowedException( sprintf(
                        __( 'Unable to delete "%s" as it\'s a dependency for "%s"%s' ),
                        $localName,
                        $foreignName,
                        $countDependency >= 1 ? ' ' . trans_choice( '{1} and :count more item.|[2,*] and :count more items.', $countDependency, ['count' => $countDependency] ) : '.'
                    ) );
                } else {
                    throw new NotAllowedException( sprintf(
                        $countDependency === 1 ?
                            __( 'Unable to delete this resource as it has %s dependency with %s item.' ) :
                            __( 'Unable to delete this resource as it has %s dependency with %s items.' ),
                        $class
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
