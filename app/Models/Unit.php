<?php

namespace App\Models;

use App\Classes\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property string         $uuid
 * @property string         $description
 * @property int            $group_id
 * @property float          $value
 * @property bool           $base_unit
 * @property \Carbon\Carbon $updated_at
 */
class Unit extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_units';

    protected $casts = [
        'base_unit' => 'boolean',
    ];

    public function setDepedencies()
    {
        return [
            ProductUnitQuantity::class => Model::dependant(
                local_name: 'name',
                local_index: 'id',
                foreign_index: 'unit_id',
                related: Model::related(
                    model: Product::class,
                    foreign_index: 'product_id',
                    local_name: 'name',
                )
            ),
        ];
    }

    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo( UnitGroup::class, 'group_id' );
    }

    /**
     * retrieve a unit using a defined
     * identifier
     *
     * @param  Query  $query
     * @param  string $identifier
     * @return Query
     */
    public function scopeIdentifier( $query, $identifier )
    {
        return $query->where( 'identifier', $identifier );
    }
}
