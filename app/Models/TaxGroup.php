<?php

namespace App\Models;

use App\Classes\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property string         $uuid
 * @property string         $description
 * @property int            $author
 * @property \Carbon\Carbon $updated_at
 */
class TaxGroup extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'taxes_groups';

    public function setDependencies()
    {
        return [
            Product::class => Model::dependant(
                local_name: 'name',
                local_index: 'id',
                foreign_name: 'tax_group_id',
                foreign_index: 'id',
            ),
        ];
    }

    /**
     * define the relationship
     *
     * @return Model\RelationShip
     */
    public function taxes()
    {
        return $this->hasMany( Tax::class );
    }
}
