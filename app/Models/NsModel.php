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

    public static function findOrFailWith( $identifier, $exception = null )
    {
        $model = static::find( $identifier );

        if ( ! $model ) {
            if ( $exception ) {
                throw $exception;
            }

            return abort( 404 );
        }

        return $model;
    }
}
