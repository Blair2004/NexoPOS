<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariation extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'products_variations';
}