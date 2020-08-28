<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement;

class Provider extends Model
{
    protected $table    =   'nexopos_' . 'providers';

    public function procurements()
    {
        return $this->hasMany( Procurement::class );
    }
}