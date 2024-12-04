<?php

namespace App\Models;

use App\Classes\Hook;
use App\Traits\NsDependable;
use Illuminate\Notifications\Notifiable;

abstract class NsModel extends NsRootModel
{
    use Notifiable, NsDependable;

    /**
     * if defined, the Crud component
     * will perform a verification to check
     * if the actual model is a dependency before deleting that.
     */
    protected $isDependencyFor = [
        // ...
    ];

    public function __construct( $attributes = [] )
    {
        parent::__construct( $attributes );

        $this->table = Hook::filter( 'ns-model-table', $this->table );
    }

    protected $dispatchableFieldsEvents = [];

    /**
     * We would like to be able to monitor
     * accurately all changes that occurs on a model
     */
    protected $oldAttributes = [];

    protected static function boot()
    {
        parent::boot();

        static::creating( function ( $model ) {
            if ( $model->hasDispatchableFields() ) {
                $model->oldAttributes = $model->getOriginal();
            }
        } );

        static::updating( function ( $model ) {
            if ( $model->hasDispatchableFields() ) {
                $model->oldAttributes = $model->getOriginal();
            }
        } );

        static::created( function ( $model ) {
            if ( $model->hasDispatchableFields() ) {
                $model->detectChanges();
            }
        } );

        static::updated( function ( $model ) {
            if ( $model->hasDispatchableFields() ) {
                $model->detectChanges();
            }
        } );
    }

    public function hasDispatchableFields()
    {
        return ! empty( $this->dispatchableFieldsEvents );
    }

    public function saveWithRelationships( array $relationships, $options = [] )
    {
        $this->mergeAttributesFromCachedCasts();

        $query = $this->newModelQuery();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ( $this->fireModelEvent( 'saving' ) === false ) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ( $this->exists ) {
            $saved = $this->isDirty() ?
                $this->performUpdate( $query ) : true;
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->doInsert( $query );

            if ( ! $this->getConnectionName() &&
                $connection = $query->getConnection() ) {
                $this->setConnection( $connection->getName() );
            }
        }

        // We'll now sync the relationship to ensure they
        // have access to the model's ID
        foreach ( $relationships as $relation => $data ) {
            $this->$relation()->saveMany( $data );
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ( $saved ) {

            // The created event should be dispatched when all the relationship
            // have been saved and the model is ready to be used.
            $this->fireModelEvent( 'created', false );
            $this->finishSave( $options );
        }

        return $saved;
    }

    public function setOldAttributes( array $attributes )
    {
        $this->oldAttributes = $attributes;
    }

    protected function detectChanges()
    {
        /**
         * It must only trigger the events if the model
         * has the $dispatchableFieldsEvents property defined
         */
        if ( $this->dispatchableFieldsEvents ) {
            $currentAttributes = array_filter( $this->getAttributes(), function ( $value ) {
                return is_string( $value ) || is_numeric( $value ) || is_bool( $value );
            } );

            $oldAttributes = array_filter( $this->oldAttributes, function ( $value ) {
                return is_string( $value ) || is_numeric( $value ) || is_bool( $value );
            } );

            $changedAttributes = array_diff_assoc( $currentAttributes, $oldAttributes );

            if ( ! empty( $changedAttributes ) ) {
                // Dispatch the "changed" event for the entire model
                $this->fireModelEvent( 'changed', false );

                // Check for specific field changes and dispatch events accordingly
                foreach ( $this->dispatchableFieldsEvents as $field => $class ) {
                    if ( array_key_exists( $field, $changedAttributes ) ) {
                        event( new $class( $this, $this->oldAttributes[$field] ?? null, $this->getAttribute( $field ) ) );
                    }
                }
            }
        }
    }

    /**
     * Get the event map for the model.
     *
     * @return array
     */
    public function getChangedEventPayload()
    {
        return [
            'old' => $this->oldAttributes,
            'new' => $this->getAttributes(),
            'changed' => array_keys( array_diff_assoc( $this->getAttributes(), $this->oldAttributes ) ),
        ];
    }
}
