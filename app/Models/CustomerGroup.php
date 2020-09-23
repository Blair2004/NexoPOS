<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerGroup extends Model
{
    use HasFactory;
    
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