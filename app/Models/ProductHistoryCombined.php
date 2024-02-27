<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int            $id
 * @property string         $name
 * @property int            $product_id
 * @property int            $unit_id
 * @property float          $initial_quantity
 * @property float          $sold_quantity
 * @property float          $procured_quantity
 * @property float          $defective_quantity
 * @property float          $final_quantity
 * @property int            $author
 * @property string         $uuid
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductHistoryCombined extends Model
{
    use HasFactory;

    protected $table = 'nexopos_' . 'products_histories_combined';

    public function scopeFor( $query, $for )
    {
        return $query->where( 'date', $for );
    }
}
