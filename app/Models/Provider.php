<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Procurement;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Provider extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'providers';
    protected $guarded  =   [];
    
    public function procurements()
    {
        return $this->hasMany( Procurement::class );
    }
}