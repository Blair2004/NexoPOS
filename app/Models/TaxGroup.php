<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxGroup extends Model
{
    protected $table    =   'nexopos_' . 'taxes_groups';

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function taxes()
    {
        return $this->hasMany( Tax::class );
    }
}