<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tax extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'taxes';
}