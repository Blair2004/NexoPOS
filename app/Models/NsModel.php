<?php

namespace App\Models;

use App\Classes\Hook;
use Illuminate\Notifications\Notifiable;

abstract class NsModel extends NsRootModel
{
    use Notifiable;

    public function __construct( $attributes = [] )
    {
        parent::__construct( $attributes );

        $this->table    =   Hook::filter( 'ns-model-table', $this->table );
    }
}
