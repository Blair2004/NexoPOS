<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $table    =   'nexopos_' . 'taxes';

    /**
     * @schema
     * -> id
     * -> name
     * -> type
     * -> description
     * -> rate
     * -> parent_id
     * -> author
     * -> uuid
     */

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function parentTax()
    {
        return $this->belongsTo( self::class, 'parent_id' );
    }

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function subTaxes()
    {
        return $this->hasMany( self::class, 'parent_id' );
    }
}