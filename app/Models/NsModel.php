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
            $model->oldAttributes = $model->getOriginal();
        } );

        static::updating( function ( $model ) {
            $model->oldAttributes = $model->getOriginal();
        } );

        static::created( function ( $model ) {
            $model->detectChanges();
        } );

        static::updated( function ( $model ) {
            $model->detectChanges();
        } );
    }

    protected function detectChanges()
    {
        $changedAttributes = array_diff_assoc( $this->getAttributes(), $this->oldAttributes );

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
