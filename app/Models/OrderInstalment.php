<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderInstalment extends NsModel
{
    use HasFactory;

    public $timestamps       =   false;
    
    protected $table    =   'nexopos_' . 'orders_instalments';
}