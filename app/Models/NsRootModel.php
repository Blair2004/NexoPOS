<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class NsRootModel extends Model
{
    /**
     * While saving model, this will
     * use the timezone defined on the settings
     */
    public function freshTimestamp()
    {
        return ns()->date->getNow();
    }

    /**
     * Perform a model insert operation.
     *
     * @return bool
     */
    protected function doInsert( Builder $query )
    {
        if ( $this->usesUniqueIds() ) {
            $this->setUniqueIds();
        }

        if ( $this->fireModelEvent( 'creating' ) === false ) {
            return false;
        }

        // First we'll need to create a fresh query instance and touch the creation and
        // update timestamps on this model, which are maintained by us for developer
        // convenience. After, we will just continue saving these model instances.
        if ( $this->usesTimestamps() ) {
            $this->updateTimestamps();
        }

        // If the model has an incrementing key, we can use the "insertGetId" method on
        // the query builder, which will give us back the final inserted ID for this
        // table from the database. Not all tables have to be incrementing though.
        $attributes = $this->getAttributesForInsert();

        if ( $this->getIncrementing() ) {
            $this->insertAndSetId( $query, $attributes );
        }

        // If the table isn't incrementing we'll simply insert these attributes as they
        // are. These attribute arrays must contain an "id" column previously placed
        // there by the developer as the manually determined key for these models.
        else {
            if ( empty( $attributes ) ) {
                return true;
            }

            $query->insert( $attributes );
        }

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = true;

        $this->wasRecentlyCreated = true;

        return true;
    }
}
