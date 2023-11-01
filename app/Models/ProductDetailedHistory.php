<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property integer $id
 * @property string $name
 * @property integer $product_id
 * @property integer $unit_id
 * @property float $initial_quantity
 * @property float $sold_quantity
 * @property float $procured_quantity
 * @property float $defective_quantity
 * @property float $final_quantity
 * @property integer $author
 * @property string $uuid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductDetailedHistory extends Model
{
    use HasFactory;

    protected $table = 'nexopos_' . 'products_detailed_history';

    public function scopeFor( $query, $for )
    {
        return $query->where( 'date', $for );
    }
}
