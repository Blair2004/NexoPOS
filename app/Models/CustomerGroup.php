<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    protected $table    =   'nexopos_' . 'customers_groups';

    /**
     * define the relationship
     * @return Model\RelationShip
     */
    public function customers()
    {
        return $this->hasMany( Customer::class, 'group_id' );
    }
}