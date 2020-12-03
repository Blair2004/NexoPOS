<?php

namespace App\Models;

use App\Classes\Hook;
use Illuminate\Database\Eloquent\Model;

abstract class NsModel extends Model
{
    public function __construct( $attributes = [] )
    {
        parent::__construct( $attributes );
        $this->table    =   Hook::filter( 'ns-model-table', $this->table );
    }
}
