<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'providers';

    public function procurements()
    {
        return $this->hasMany( Procurement::class );
    }
}