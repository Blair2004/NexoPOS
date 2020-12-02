<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxGroup extends NsModel
{
    use HasFactory;
    
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