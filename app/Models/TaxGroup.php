<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
