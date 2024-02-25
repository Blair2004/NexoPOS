<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int            $id
 * @property int            $author
 * @property string         $uuid
 * @property float          $value
 * @property \Carbon\Carbon $updated_at
 */
class ProductTax extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'products_taxes';

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function parentTax()
    {
        return $this->belongsTo( self::class, 'parent_id' );
    }

    /**
     * find combinaison of product id
     * and tax id
     *
     * @param array {product_id: int, tax_id: int}
     * @return Query
     */
    public function scopeFindMatch( $query, $data )
    {
        extract( $data );

        /**
         * -> product_id
         * -> tax_id
         */
        return $query->where( 'tax_id', $tax_id )
            ->where( 'product_id', $product_id );
    }
}
