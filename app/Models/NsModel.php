<?php

namespace App\Models;

use App\Classes\Hook;
use Illuminate\Notifications\Notifiable;

abstract class NsModel extends NsRootModel
{
    use Notifiable;

    /**
     * if defined, the Crud component
     * will perform a verification to check
     * if the actual model is a dependency before deleting that.
     */
    protected $isDependencyFor     =   [
        // ...
    ];

    public function getDeclaredDependencies()
    {
        return $this->isDependencyFor;
    }

    public function __construct( $attributes = [] )
    {
        parent::__construct( $attributes );

        $this->table    =   Hook::filter( 'ns-model-table', $this->table );
    }
}
